<?php

declare(strict_types=1);

namespace Nektria\Listener;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Nektria\Dto\Clock;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Message\Event;
use Nektria\Service\AlertService;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Nektria\Service\VariableCache;
use Nektria\Util\JsonUtil;
use Nektria\Util\MessageStamp\ContextStamp;
use Nektria\Util\StringUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;

use function in_array;

abstract class MessageListener implements EventSubscriberInterface
{
    private float $executionTime;

    private string $messageStartedAt;

    private string $messageCompletedAt;

    public function __construct(
        private readonly LogService $logService,
        private readonly VariableCache $variableCache,
        private readonly UserServiceInterface $userService,
        private readonly ContextService $contextService,
        private readonly EntityManagerInterface $entityManager,
        private readonly AlertService $notificationService
    ) {
        $this->executionTime = microtime(true);
        $this->messageCompletedAt = Clock::new()->iso8601String();
        $this->messageStartedAt = $this->messageCompletedAt;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'onWorkerMessageReceived',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
            WorkerMessageFailedEvent::class => 'onMessengerException'
        ];
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        /** @var ContextStamp|null $contextStamp */
        $contextStamp = $event->getEnvelope()->last(ContextStamp::class);
        if ($contextStamp !== null) {
            $this->contextService->setContext($contextStamp->context());
            $this->contextService->setTraceId($contextStamp->traceId());
            $this->contextService->setTenantId($contextStamp->tenantId());
            if ($contextStamp->tenantId() !== null) {
                $this->userService->authenticateSystem($contextStamp->tenantId());
            }
        }

        $this->messageStartedAt = Clock::new()->iso8601String();
        $this->executionTime = microtime(true);
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $receivers = [
            'crons' => 'yieldmanager.messages.crons',
            'low' => 'yieldmanager.messages.low',
            'default' => 'yieldmanager.messages.default',
            'high' => 'yieldmanager.messages.high'
        ];

        $queue = $receivers[$event->getReceiverName()] ?? 'unkown';
        $minutesTimestamp = Clock::new()->timestamp('minutes');
        $key = "{$queue}_{$minutesTimestamp}";
        $amount = $this->variableCache->readInt($key);
        $this->variableCache->saveInt($key, $amount + 1, 604800);

        $this->messageCompletedAt = Clock::new()->iso8601String();
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Event) {
            $encoders = [new JsonEncoder()];
            $normalizers = [new PropertyNormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $data = JsonUtil::decode($serializer->serialize($message, 'json'));
            $messageClass = StringUtil::className($message);
            $resume = "/{$messageClass}/{$message->ref()}";
            $time = max(0.001, round(microtime(true) - $this->executionTime, 3)) . 's';

            $this->logService->info([
                'context' => 'messenger',
                'event' => $message::class,
                'body' => $data,
                'executionTime' => $time,
                'messageReceivedAt' => $this->messageStartedAt,
                'messageCompletedAt' => $this->messageCompletedAt,
                'httpRequest' => [
                    'requestUrl' => "/{$messageClass}/{$message->ref()}",
                    'requestMethod' => 'QUEUE',
                    'status' => 200,
                    'latency' => $time
                ]
            ], $resume);

            $this->userService->clearAuthentication();
        }

        try {
            $this->entityManager->clear();
        } catch (Throwable) {
        }
        gc_collect_cycles();
    }

    public function onMessengerException(WorkerMessageFailedEvent $event): void
    {
        $this->messageCompletedAt = Clock::new()->iso8601String();
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Event) {
            $encoders = [new JsonEncoder()];
            $normalizers = [new PropertyNormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $data = JsonUtil::decode($serializer->serialize($message, 'json'));
            $exception = $event->getThrowable();
            $class = $message::class;
            $classHash = str_replace('\\', '_', $class);
            $messageClass = StringUtil::className($message);

            if ($exception instanceof HandlerFailedException && $exception->getPrevious() !== null) {
                $exception = $exception->getPrevious();
            }

            if ($exception instanceof DriverException || $exception instanceof ConnectionException) {
                touch('/tmp/entity_manager_is_closed');
            }

            $this->logService->exception($exception, [
                'context' => 'messenger',
                'event' => $class,
                'body' => $data,
                'messageReceivedAt' => $this->messageStartedAt,
                'messageCompletedAt' => $this->messageCompletedAt,
                'httpRequest' => [
                    'requestUrl' => "/{$messageClass}/{$message->ref()}",
                    'requestMethod' => 'QUEUE',
                    'status' => 500,
                    'latency' => max(0.001, round(microtime(true) - $this->executionTime, 3)) . 's'
                ]
            ]);

            $tenantName = $this->userService->user()?->tenant->name ?? 'none';
            $value = $this->variableCache->hasKey("{$tenantName}-messenger-{$classHash}");

            if (!$value) {
                $this->variableCache->saveKey("{$tenantName}-messenger-{$classHash}");

                $ignoreMessages = [
                    'Redelivered message from AMQP detected that will be rejected and trigger the retry logic.'
                ];

                if (!in_array($exception->getMessage(), $ignoreMessages, true)) {
                    $this->notificationService->sendMessage(
                        'bugs',
                        [
                            'content' => $exception->getMessage(),
                        ]
                    );
                }
            }
        }

        $this->userService->clearAuthentication();

        try {
            $this->entityManager->clear();
        } catch (Throwable) {
        }

        gc_collect_cycles();
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Listener;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Nektria\Document\Document;
use Nektria\Document\ThrowableDocument;
use Nektria\Dto\Clock;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Message\Command;
use Nektria\Message\Event;
use Nektria\Message\Query;
use Nektria\Service\AlertService;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Nektria\Service\SharedVariableCache;
use Nektria\Service\VariableCache;
use Nektria\Util\JsonUtil;
use Nektria\Util\MessageStamp\ContextStamp;
use Nektria\Util\MessageStamp\RetryStamp;
use Nektria\Util\StringUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
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

    private string $messageCompletedAt;

    private string $messageStartedAt;

    public function __construct(
        private readonly AlertService $alertService,
        private readonly ContextService $contextService,
        private readonly LogService $logService,
        private readonly UserServiceInterface $userService,
        private readonly VariableCache $variableCache,
        private readonly BusInterface $bus,
        private readonly SharedVariableCache $sharedVariableCache,
    ) {
        $this->executionTime = microtime(true);
        $this->messageCompletedAt = Clock::now()->iso8601String();
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
            WorkerMessageFailedEvent::class => 'onMessengerException',
            WorkerStoppedEvent::class => 'onWorkerStoppedEvent',
        ];
    }

    public function onMessengerException(WorkerMessageFailedEvent $event): void
    {
        $retryStamp = $event->getEnvelope()->last(RetryStamp::class);
        $transportStamp = $event->getEnvelope()->last(TransportNamesStamp::class);
        $this->messageCompletedAt = Clock::now()->iso8601String();
        $message = $event->getEnvelope()->getMessage();
        $maxRetries = 1;

        if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
            $this->decreaseCounter($message);
        }

        if ($retryStamp !== null) {
            $maxRetries = $retryStamp->maxRetries;
            $nextTry = $retryStamp->currentTry + 1;
            if ($nextTry <= $retryStamp->maxRetries) {
                $transport = null;
                if ($transportStamp !== null) {
                    $transport = $transportStamp->getTransportNames()[0];
                }

                if ($message instanceof Command) {
                    $this->bus->dispatchCommand(
                        $message,
                        transport: $transport,
                        delayMs: $retryStamp->intervalMs,
                        retryOptions: [
                            'currentTry' => $nextTry,
                            'maxTries' => $retryStamp->maxRetries,
                            'interval' => $retryStamp->intervalMs,
                        ],
                    );
                } elseif ($message instanceof Event) {
                    $this->bus->dispatchEvent($message);
                }
            }
        }

        if ($message instanceof Command || $message instanceof Event) {
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

            if (!($exception instanceof ThrowableDocument)) {
                $exception = new ThrowableDocument($exception);
            }

            $originalException = $exception->throwable;
            if ($originalException instanceof DriverException || $originalException instanceof ConnectionException) {
                touch('/tmp/entity_manager_is_closed');
            }

            $exchangeName = '?';
            $exchangeStamp = $event->getEnvelope()->last(AmqpReceivedStamp::class);
            if ($exchangeStamp !== null) {
                $exchangeName = $exchangeStamp->getAmqpEnvelope()->getExchangeName();
            }

            $this->logService->temporalLogs();
            $this->logService->exception($originalException, [
                'context' => 'messenger',
                'event' => $class,
                'body' => $data,
                'messageReceivedAt' => $this->messageStartedAt,
                'messageCompletedAt' => $this->messageCompletedAt,
                'queue' => $exchangeName,
                'maxRetries' => $maxRetries,
                'httpRequest' => [
                    'requestUrl' => "/{$messageClass}/{$message->ref()}",
                    'requestMethod' => 'QUEUE',
                    'status' => 500,
                    'latency' => max(0.001, round(microtime(true) - $this->executionTime, 3)) . 's',
                ],
            ]);

            $tenantName = $this->userService->user()?->tenant->name ?? 'none';

            $key = "{$tenantName}-messenger-{$classHash}";
            $key2 = "{$tenantName}-messenger-{$classHash}_count";
            if ($this->contextService->env() === ContextService::DEV || $this->variableCache->refreshKey($key)) {
                $ignoreMessages = [
                    'Redelivered message from AMQP detected that will be rejected and trigger the retry logic.',
                ];

                $times = $this->variableCache->readInt($key2, 1);

                if (!in_array($originalException->getMessage(), $ignoreMessages, true)) {
                    $sendAlert = true;
                    if ($originalException instanceof ResourceNotFoundException) {
                        $sendAlert = false;
                    }

                    if ($sendAlert) {
                        $this->alertService->sendThrowable(
                            $this->userService->user()?->tenant->name ?? 'none',
                            'RABBIT',
                            "/{$messageClass}/{$message->ref()}",
                            $data,
                            $exception,
                            $times,
                        );
                    }
                }

                $this->variableCache->saveInt($key2, 0);
            } else {
                $times = $this->variableCache->readInt($key2);
                $this->variableCache->saveInt($key2, $times + 1);
            }
        }

        $this->userService->clearAuthentication();

        $this->cleanMemory();

        gc_collect_cycles();
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->bus->dispatchDelayedEvents();
        $this->messageCompletedAt = Clock::now()->iso8601String();
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
            $this->decreaseCounter($message);
        }

        if ($message instanceof Command || $message instanceof Event) {
            $encoders = [new JsonEncoder()];
            $normalizers = [new PropertyNormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $data = JsonUtil::decode($serializer->serialize($message, 'json'));
            $messageClass = StringUtil::className($message);
            $resume = "/{$messageClass}/{$message->ref()}";
            $time = max(0.001, round(microtime(true) - $this->executionTime, 3)) . 's';

            $exchangeName = '?';
            $exchangeStamp = $event->getEnvelope()->last(AmqpReceivedStamp::class);
            if ($exchangeStamp !== null) {
                $exchangeName = $exchangeStamp->getAmqpEnvelope()->getExchangeName();
            }

            $this->logService->debug([
                'context' => 'messenger',
                'event' => $message::class,
                'body' => $data,
                'executionTime' => $time,
                'messageReceivedAt' => $this->messageStartedAt,
                'messageCompletedAt' => $this->messageCompletedAt,
                'queue' => $exchangeName,
                'httpRequest' => [
                    'requestUrl' => $resume,
                    'requestMethod' => 'QUEUE',
                    'status' => 200,
                    'latency' => $time,
                ],
            ], $resume);

            $this->userService->clearAuthentication();
        }

        $this->cleanMemory();

        gc_collect_cycles();
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
            $this->increaseCounter($message);
        }

        try {
            /** @var ContextStamp|null $contextStamp */
            $contextStamp = $event->getEnvelope()->last(ContextStamp::class);
            if ($contextStamp !== null) {
                $this->contextService->setContext('rabbit');
                $this->contextService->setTraceId($contextStamp->traceId);
                if ($contextStamp->tenantId !== null) {
                    $this->userService->authenticateSystem($contextStamp->tenantId);
                }
            }

            $this->messageStartedAt = Clock::now()->iso8601String();
            $this->executionTime = microtime(true);
        } catch (Throwable $e) {
            $this->alertService->sendThrowable(
                $this->userService->user()?->tenant->name ?? 'none',
                'RABBIT',
                '',
                [],
                new ThrowableDocument($e),
            );
        }
    }

    public function onWorkerStoppedEvent(): void
    {
        $this->cleanMemory();

        gc_collect_cycles();
    }

    abstract protected function cleanMemory(): void;

    /**
     * @param Event|Query<Document>|Command $message
     */
    private function decreaseCounter(Event | Query | Command $message): void
    {
        $project = $this->contextService->project();
        $clzz = $message::class;
        $this->sharedVariableCache->multi();
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages', '[]'));
        $key = "{$project}_{$clzz}";
        if (!in_array($key, $data, true)) {
            $data[] = $key;
        }
        sort($data);
        $this->sharedVariableCache->saveString('bus_messages', JsonUtil::encode($data), 86400);

        $times = $this->sharedVariableCache->readInt("bus_messages_{$key}") - 1;
        $this->sharedVariableCache->saveInt("bus_messages_{$key}", $times);
    }

    /**
     * @param Event|Query<Document>|Command $message
     */
    private function increaseCounter(Event | Query | Command $message): void
    {
        $project = $this->contextService->project();
        $clzz = $message::class;
        $this->sharedVariableCache->multi();
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages', '[]'));
        $key = "{$project}_{$clzz}";
        if (!in_array($key, $data, true)) {
            $data[] = $key;
        }
        sort($data);
        $this->sharedVariableCache->saveString('bus_messages', JsonUtil::encode($data), 86400);

        $times = $this->sharedVariableCache->readInt("bus_messages_{$key}") - 1;
        $this->sharedVariableCache->saveInt("bus_messages_{$key}", $times);
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\ThrowableDocument;
use Nektria\Util\JsonUtil;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Throwable;

use function in_array;

use const PHP_EOL;

class LogService
{
    public const string DEBUG = 'DEBUG';

    public const string EMERGENCY = 'EMERGENCY';

    public const string ERROR = 'ERROR';

    public const string INFO = 'INFO';

    public const string WARNING = 'WARNING';

    /** @var resource|false */
    private $channel;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly SharedLogCache $sharedLogCache,
    ) {
        $this->channel = fopen('php://stderr', 'wb');

        if ($this->contextService->isLocalEnvironament()) {
            $this->channel = false;
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function debug(array $payload, string $message, bool $ignoreRedis = false): void
    {
        if (!$ignoreRedis && ($this->channel === false || !$this->contextService->debugMode())) {
            $this->sharedLogCache->addLog([
                'context' => $this->contextService->context(),
                'message' => $message,
                'payload' => $payload,
                'project' => $this->contextService->project(),
                'tenant' => $this->contextService->tenantName() ?? 'none',
                'tenantId' => $this->contextService->tenantId() ?? 'none',
                'userId' => $this->contextService->userId() ?? 'none',
            ]);

            return;
        }

        if ($this->channel === false) {
            return;
        }

        $data = $this->build($payload, $message, self::DEBUG);
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     */
    public function error(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, self::ERROR);
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $extra
     */
    public function exception(Throwable $exception, array $extra = [], bool $asWarning = false): void
    {
        if ($this->channel === false) {
            return;
        }

        $ignoreList = [
            RejectRedeliveredMessageException::class,
            RecoverableMessageHandlingException::class,
        ];

        if (in_array($exception::class, $ignoreList, true)) {
            return;
        }

        $tmp = new ThrowableDocument($exception);
        $clearTrace = $tmp->trace();

        try {
            $data = [
                'message' => $exception->getMessage(),
                'logName' => 'projects/nektria/logs/error',
                'severity' => $asWarning ? self::WARNING : self::EMERGENCY,
                'logging.googleapis.com/labels' => [
                    'app' => $this->contextService->project(),
                    'context' => $this->contextService->context(),
                    'env' => $this->contextService->env(),
                    'tenant' => $this->contextService->tenantName() ?? 'none',
                    'tenantId' => $this->contextService->tenantId() ?? 'none',
                    'userId' => $this->contextService->userId() ?? 'none',
                ],
                'logging.googleapis.com/trace_sampled' => false,
            ];

            $data['logging.googleapis.com/trace'] = $this->contextService->traceId();

            $payload = [
                'type' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $clearTrace,
                'extra' => $extra,
            ];

            $data = array_merge($payload, $data);
            fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
        } catch (Throwable) {
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function info(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, self::INFO);
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     */
    public function send(
        string $level,
        array $payload,
        string $message,
    ): void {
        match ($level) {
            self::INFO => $this->info($payload, $message),
            self::WARNING => $this->warning($payload, $message),
            self::DEBUG => $this->debug($payload, $message),
            self::ERROR => $this->error($payload, $message),
            default => false,
        };
    }

    public function temporalLogs(): void
    {
        if ($this->channel === false) {
            return;
        }

        $logs = $this->sharedLogCache->getLogs();

        foreach ($logs as $log) {
            $data = [
                'message' => $log['message'],
                'logName' => "projects/nektria/logs/{$log['project']}",
                'severity' => self::DEBUG,
                'logging.googleapis.com/labels' => [
                    'app' => $log['project'],
                    'context' => $log['context'],
                    'env' => $this->contextService->env(),
                    'tenant' => $this->contextService->tenantName() ?? 'none',
                    'tenantId' => $log['tenantId'] ?? 'none',
                    'userId' => $log['userId'] ?? 'none',
                ],
                'logging.googleapis.com/trace' => $this->contextService->traceId(),
                'logging.googleapis.com/trace_sampled' => false,
            ];

            $data = array_merge($log['payload'], $data);
            fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function warning(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, self::WARNING);
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     * @return mixed[]
     */
    private function build(
        array $payload,
        string $message,
        string $level
    ): array {
        $data = [
            'message' => $message,
            'logName' => "projects/nektria/logs/{$this->contextService->project()}",
            'severity' => $level,
            'logging.googleapis.com/labels' => [
                'app' => $this->contextService->project(),
                'context' => $this->contextService->context(),
                'env' => $this->contextService->env(),
                'tenant' => $this->contextService->tenantName() ?? 'none',
                'tenantId' => $this->contextService->tenantId(),
                'userId' => $this->contextService->userId() ?? 'none',
            ],
            'logging.googleapis.com/trace' => $this->contextService->traceId(),
            'logging.googleapis.com/trace_sampled' => false,
        ];

        return array_merge($payload, $data);
    }
}

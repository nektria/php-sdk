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
    /** @var resource|false */
    private $channel;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly string $env,
        private readonly string $defaultLogLevel,
    ) {
        $this->channel = fopen('php://stderr', 'wb');

        if ($env === 'dev' || $env === 'test') {
            $this->channel = false;
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function log(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, 'DEFAULT');
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     */
    public function default(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, $this->defaultLogLevel);
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     */
    public function info(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, 'INFO');
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     */
    public function warning(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, 'WARNING');
        fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
    }

    /**
     * @param mixed[] $payload
     */
    public function debug(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }

        if ($this->contextService->debug()) {
            $data = $this->build($payload, $message, 'DEBUG');
            fwrite($this->channel, JsonUtil::encode($data) . PHP_EOL);
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function error(array $payload, string $message): void
    {
        if ($this->channel === false) {
            return;
        }
        $data = $this->build($payload, $message, 'ERROR');
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
                'severity' => $asWarning ? 'WARNING' : 'EMERGENCY',
                'logging.googleapis.com/labels' => [
                    'env' => $this->env,
                    'app' => $this->contextService->project(),
                    'tenant' => $this->contextService->tenantId(),
                    'user' => $this->contextService->userId(),
                    'name' => 'error',
                ],
                'logging.googleapis.com/trace_sampled' => false
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
     * @return mixed[]
     */
    public function build(
        array $payload,
        string $message,
        string $level
    ): array {
        $data = [
            'message' => $message,
            'logName' => "projects/nektria/logs/{$this->contextService->project()}",
            'severity' => $level,
            'logging.googleapis.com/labels' => [
                'context' => $this->contextService->context(),
                'env' => $this->env,
                'app' => $this->contextService->project(),
                'tenant' => $this->contextService->tenantId(),
                'user' => $this->contextService->userId(),
            ],
            'logging.googleapis.com/trace' => $this->contextService->traceId(),
            'logging.googleapis.com/trace_sampled' => false
        ];

        return array_merge($payload, $data);
    }
}

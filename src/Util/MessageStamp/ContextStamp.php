<?php

declare(strict_types=1);

namespace Nektria\Util\MessageStamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ContextStamp implements StampInterface
{
    private string $traceId;

    private string $context;

    private string $tenantId;

    private string $userId;

    public function __construct(string $context, string $traceId, string $tenantId, string $userId)
    {
        $this->traceId = $traceId;
        $this->context = $context;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function userId(): string
    {
        return $this->userId;
    }
}

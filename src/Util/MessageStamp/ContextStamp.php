<?php

declare(strict_types=1);

namespace Nektria\Util\MessageStamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ContextStamp implements StampInterface
{
    private string $traceId;

    private string $context;

    private ?string $tenantId;

    public function __construct(string $context, string $traceId, ?string $tenantId)
    {
        $this->traceId = $traceId;
        $this->context = $context;
        $this->tenantId = $tenantId;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }
}

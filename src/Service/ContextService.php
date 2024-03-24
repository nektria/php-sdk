<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Util\StringUtil;

class ContextService
{
    private string $context;

    private string $traceId;

    private string $tenantId;

    private string $userId;

    public function __construct(
        private readonly string $env
    ) {
        $this->context = 'none';
        $this->traceId = StringUtil::uuid4();
        $this->tenantId = 'anon.';
        $this->userId = 'anon.';
    }

    public function context(): string
    {
        return $this->context;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function env(): string
    {
        return $this->env;
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }
}

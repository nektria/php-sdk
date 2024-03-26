<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Util\StringUtil;
use Nektria\Util\ValidateOpt;

class ContextService
{
    public const CONTEXT_PUBLIC = 'public';

    public const CONTEXT_PUBLIC_V2 = 'public_v2';

    public const CONTEXT_INTERNAL = 'internal';

    public const CONTEXT_COMMON = 'common';

    public const CONTEXT_ADMIN = 'admin';

    public const CONTEXT_SYSTEM = 'system';

    private string $context;

    private string $traceId;

    private ?string $tenantId;

    private ?string $userId;

    private string $project;

    private string $env;

    public function __construct(
        string $env,
        string $project
    ) {
        $this->env = $env;
        $this->context = self::CONTEXT_COMMON;
        $this->traceId = StringUtil::uuid4();
        $this->project = $project;
        $this->userId = null;
        $this->tenantId = null;
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

    public function project(): string
    {
        return $this->project;
    }

    public function debug(): bool
    {
        return $this->env === 'dev';
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(?string $tenantId): void
    {
        ValidateOpt::uuid4($tenantId);
        $this->tenantId = $tenantId;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        ValidateOpt::uuid4($userId);
        $this->userId = $userId;
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Util\StringUtil;
use Nektria\Util\ValidateOpt;

class ContextService
{
    public const PUBLIC = 'public';

    public const PUBLIC_V2 = 'public_v2';

    public const INTERNAL = 'internal';

    public const COMMON = 'common';

    public const ADMIN = 'admin';

    public const SYSTEM = 'system';

    public const TEST = 'test';

    public const DEV = 'dev';

    public const QA = 'qa';

    public const STAGING = 'staging';

    public const PROD = 'prod';

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
        $this->context = self::COMMON;
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

    public function isDev(): bool
    {
        return $this->env === self::DEV;
    }

    public function isTest(): bool
    {
        return $this->env === self::TEST;
    }

    public function isProd(): bool
    {
        return $this->env === self::PROD;
    }

    public function isStaging(): bool
    {
        return $this->env === self::STAGING;
    }

    public function isQA(): bool
    {
        return $this->env === self::QA;
    }

    public function isPlayEnvironment(): bool
    {
        return $this->isDev() || $this->isTest() || $this->isQA();
    }
}

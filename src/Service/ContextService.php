<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Util\StringUtil;
use Nektria\Util\ValidateOpt;

class ContextService
{
    public const string ADMIN = 'admin';

    public const string COMMON = 'common';

    public const string DEV = 'dev';

    public const string INTERNAL = 'internal';

    public const string PROD = 'prod';

    public const string PUBLIC = 'public';

    public const string PUBLIC_V2 = 'public_v2';

    public const string QA = 'qa';

    public const string STAGING = 'staging';

    public const string SYSTEM = 'system';

    public const string TEST = 'test';

    public static ?ContextService $dummyCS;

    private string $context;

    private bool $forceSync;

    private ?string $tenantId;

    private string $traceId;

    private ?string $userId;

    public function __construct(
        private readonly SharedVariableCache $sharedVariableCache,
        private readonly string $env,
        private readonly string $project,
    ) {
        $this->context = self::COMMON;
        $this->traceId = StringUtil::uuid4();
        $this->userId = null;
        $this->tenantId = null;
        $this->forceSync = false;
    }

    public static function dummy(): self
    {
        self::$dummyCS ??= new self(new SharedVariableCache('', ''), self::INTERNAL, 'dummy');

        return self::$dummyCS;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function debugMode(): bool
    {
        return
            $this->isLocalEnvironament()
            || $this->sharedVariableCache->hasKey("debug_bbf6c8f_{$this->project}")
            || $this->traceId === '3e65f252-8a06-4361-a264-0cd60b7a26c5';
    }

    /**
     * @param string[] $projects
     * @return array<string, bool>
     */
    public function debugModes(array $projects): array
    {
        $data = [];
        foreach ($projects as $project) {
            $key = "debug_bbf6c8f_{$project}";
            $data[$project] = $this->isLocalEnvironament() || $this->sharedVariableCache->hasKey($key);
        }

        return $data;
    }

    public function env(): string
    {
        return $this->env;
    }

    public function forceSync(): bool
    {
        return $this->forceSync;
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    public function isDev(): bool
    {
        return $this->env === self::DEV;
    }

    public function isLocalEnvironament(): bool
    {
        return $this->env === self::DEV;
    }

    public function isPlayEnvironment(): bool
    {
        return $this->isDev() || $this->isTest() || $this->isQA();
    }

    public function isProd(): bool
    {
        return $this->env === self::PROD;
    }

    public function isPublic(): bool
    {
        return $this->context === self::PUBLIC || $this->context === self::PUBLIC_V2;
    }

    public function isQA(): bool
    {
        return $this->env === self::QA;
    }

    public function isRealEnvironament(): bool
    {
        return $this->isStaging() || $this->isProd();
    }

    public function isStaging(): bool
    {
        return $this->env === self::STAGING;
    }

    public function isTest(): bool
    {
        return $this->env === self::TEST;
    }

    public function project(): string
    {
        return $this->project;
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    /**
     * @param string[] $projects
     */
    public function setDebugMode(bool $enable, array $projects, int $ttl): void
    {
        foreach ($projects as $project) {
            $key = "debug_bbf6c8f_{$project}";
            if ($enable) {
                $this->sharedVariableCache->saveKey($key, $ttl);
            } else {
                $this->sharedVariableCache->deleteKey($key);
            }
        }
    }

    public function setForceSync(bool $forceSync): void
    {
        $this->forceSync = $forceSync;
    }

    public function setTenantId(?string $tenantId): void
    {
        ValidateOpt::uuid4($tenantId);
        $this->tenantId = $tenantId;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function setUserId(?string $userId): void
    {
        ValidateOpt::uuid4($userId);
        $this->userId = $userId;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }
}

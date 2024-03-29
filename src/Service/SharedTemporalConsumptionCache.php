<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<array<string, array<string, int>>>
 */
class SharedTemporalConsumptionCache extends SharedRedisCache
{
    public function __construct(
        private readonly ContextService $contextService,
        string $redisDsn,
        string $env
    ) {
        parent::__construct($redisDsn, $env);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function read(string $tenantId): array
    {
        return $this->getItem($tenantId) ?? [];
    }

    public function remove(string $tenantId): void
    {
        $this->removeItem($tenantId);
    }

    public function increase(string $tenantId, string $path): void
    {
        $data = $this->read($tenantId);
        $data[$this->contextService->project()][$path] ??= 0;
        ++$data[$this->contextService->project()][$path];
        $this->setItem($tenantId, $data);
    }
}

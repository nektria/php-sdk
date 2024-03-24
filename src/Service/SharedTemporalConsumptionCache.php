<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<array<string, array<string, int>>>
 */
class SharedTemporalConsumptionCache extends SharedRedisCache
{
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
        $data['yieldmanager'][$path] ??= 0;
        ++$data['yieldmanager'][$path];
        $this->setItem($tenantId, $data);
    }
}

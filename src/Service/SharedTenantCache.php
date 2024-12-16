<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Dto\TenantMetadata;
use Nektria\Infrastructure\SharedRedisCache;

/**
 * @phpstan-type TenantMetadataArray mixed
 *
 * @extends SharedRedisCache<array{
 *     aiAssistantId: string|null,
 *     id: string,
 *     name: string,
 *     metadata: TenantMetadataArray
 * }>
 */
class SharedTenantCache extends SharedRedisCache
{
    public function read(string $key): ?Tenant
    {
        $data = $this->getItem($key);
        if ($data === null) {
            return null;
        }

        return new Tenant(
            id: $data['id'],
            name: $data['name'],
            metadata: new TenantMetadata($data['metadata']),
            aiAssistantId: $data['aiAssistantId'] ?? null,
        );
    }

    public function save(Tenant $tenant): void
    {
        $this->setItem(
            $tenant->id,
            [
                'aiAssistantId' => $tenant->aiAssistantId,
                'id' => $tenant->id,
                'name' => $tenant->name,
                'metadata' => $tenant->metadata->data(),
            ],
            1209600,
        );
    }
}

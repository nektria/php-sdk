<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Dto\TenantMetadata;
use Nektria\Infrastructure\SharedRedisCache;

/**
 * @phpstan-type TenantMetadataArray array{
 *     autoDuplicateLastWeek: bool,
 *     availableTags: string[],
 *     blockWarehouse: bool,
 *     dayOffExtendsCutoff: bool,
 *     deliveryTime: number | null,
 *     ecoMode: string,
 *     expressGridDisabled: bool,
 *     extendPickingShiftsDisabled: bool,
 *     extraLongSpeed: number,
 *     forceDriverAssignation: bool,
 *     forceTags: bool,
 *     gridMode: string,
 *     gridVersion: number,
 *     gridViewerOrdersPrefix: string,
 *     ignoreRoutesOnLogsList: string[],
 *     longSpeed: number,
 *     nextStepEnabled: bool,
 *     parkingTime: number,
 *     proxyHost: string|null,
 *     recoverCoords: bool,
 *     shortSpeed: number,
 *     syncRMOrder: bool,
 *     syncRMShift: bool,
 *     syncRMWarehouse: bool,
 *     useAddressInsteadOfShopperCode: bool,
 * }
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

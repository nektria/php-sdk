<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Dto\TenantMetadata;
use Nektria\Infrastructure\SharedRedisCache;

/**
 * @phpstan-type WarehouseMetadataArray array{
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
 *     importOrdersFromFileByProxy: bool,
 *     longSpeed: number,
 *     nextStepEnabled: bool,
 *     parkingTime: number,
 *     proxyHost: string,
 *     proxyToken: string,
 *     recoverCoords: bool,
 *     sendNewOrderToProxy: bool,
 *     sendRoutesAtPickingShiftClosesAt: bool,
 *     sendRoutesByProxy: bool,
 *     shortSpeed: number,
 *     syncRMOrder: bool,
 *     syncRMShift: bool,
 *     syncRMWarehouse: bool,
 *     useAddressInsteadOfShopperCode: bool,
 * }
 *
 * @extends SharedRedisCache<array{
 *     id: string,
 *     name: string,
 *     metadata: WarehouseMetadataArray
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
            $data['id'],
            $data['name'],
            new TenantMetadata($data['metadata'])
        );
    }

    public function save(string $key, Tenant $tenant): void
    {
        $this->setItem(
            $key,
            [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'metadata' => $tenant->metadata->toArray()
            ],
            1209600
        );
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Service;

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
    /**
     * @return array{
     *      id: string,
     *      name: string,
     *      metadata: WarehouseMetadataArray
     *  }|null
     */
    public function read(string $key): ?array
    {
        return $this->getItem($key);
    }

    /**
     * @param array{
     *       id: string,
     *       name: string,
     *       metadata: WarehouseMetadataArray
     *   } $tenant
     */
    public function save(string $key, array $tenant): void
    {
        $this->setItem(
            $key,
            $tenant,
            1209600
        );
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<array{
 *       id: string,
 *       name: string,
 *       metadata: array{
 *           availableTags: string[],
 *           nextStepEnabled: boolean,
 *           forceDriverAssignation: boolean,
 *           gridMode: string,
 *           gridVersion: number,
 *           syncRMORder: boolean,
 *           syncRMShift: boolean,
 *           blockWarehouse: boolean,
 *           syncRMWarehouse: boolean,
 *           autoDuplicateLastWeek: boolean,
 *           gridViewerOrdersPrefix: string,
 *           parkingTime: number,
 *           deliveryTime: number | null,
 *           ecoMode: string,
 *           expressGridDisabled: boolean,
 *           forceTags: boolean,
 *           longSpeed: number,
 *           extraLongSpeed: number,
 *           shortSpeed: number,
 *           dayOffExtendsCutoff: boolean,
 *           recoverCoords: boolean,
 *           useAddressInsteadOfShopperCode: boolean,
 *       }
 *   }>
 */
class SharedTenantCache extends SharedRedisCache
{
    /**
     * @return array{
     *      id: string,
     *      name: string,
     *      metadata: array{
     *          availableTags: string[],
     *          nextStepEnabled: boolean,
     *          forceDriverAssignation: boolean,
     *          gridMode: string,
     *          gridVersion: number,
     *          syncRMORder: boolean,
     *          syncRMShift: boolean,
     *          blockWarehouse: boolean,
     *          syncRMWarehouse: boolean,
     *          autoDuplicateLastWeek: boolean,
     *          gridViewerOrdersPrefix: string,
     *          parkingTime: number,
     *          deliveryTime: number | null,
     *          ecoMode: string,
     *          expressGridDisabled: boolean,
     *          forceTags: boolean,
     *          longSpeed: number,
     *          extraLongSpeed: number,
     *          shortSpeed: number,
     *          dayOffExtendsCutoff: boolean,
     *          recoverCoords: boolean,
     *          useAddressInsteadOfShopperCode: boolean,
     *      }
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
     *       metadata: array{
     *           availableTags: string[],
     *           nextStepEnabled: boolean,
     *           forceDriverAssignation: boolean,
     *           gridMode: string,
     *           gridVersion: number,
     *           syncRMORder: boolean,
     *           syncRMShift: boolean,
     *           blockWarehouse: boolean,
     *           syncRMWarehouse: boolean,
     *           autoDuplicateLastWeek: boolean,
     *           gridViewerOrdersPrefix: string,
     *           parkingTime: number,
     *           deliveryTime: number | null,
     *           ecoMode: string,
     *           expressGridDisabled: boolean,
     *           forceTags: boolean,
     *           longSpeed: number,
     *           extraLongSpeed: number,
     *           shortSpeed: number,
     *           dayOffExtendsCutoff: boolean,
     *           recoverCoords: boolean,
     *           useAddressInsteadOfShopperCode: boolean,
     *       }
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

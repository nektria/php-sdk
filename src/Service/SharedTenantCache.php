<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

/**
 * @phpstan-type WarehouseMetadataArray array{
 *         id: string,
 *         name: string,
 *         metadata: array{
 *             autoDuplicateLastWeek: bool,
 *             availableTags: string[],
 *             blockWarehouse: bool,
 *             dayOffExtendsCutoff: bool,
 *             deliveryTime: number | null,
 *             ecoMode: string,
 *             expressGridDisabled: bool,
 *             extraLongSpeed: number,
 *             forceDriverAssignation: bool,
 *             forceTags: bool,
 *             gridMode: string,
 *             gridVersion: number,
 *             gridViewerOrdersPrefix: string,
 *             ignoreRoutesOnLogsList: string[],
 *             importOrdersFromFileByProxy: bool,
 *             longSpeed: number,
 *             nextStepEnabled: bool,
 *             parkingTime: number,
 *             proxyHost: string,
 *             proxyToken: string,
 *             recoverCoords: bool,
 *             sendNewOrderToProxy: bool,
 *             sendRoutesByProxy: bool,
 *             shortSpeed: number,
 *             syncRMORder: bool,
 *             syncRMShift: bool,
 *             syncRMWarehouse: bool,
 *             useAddressInsteadOfShopperCode: bool,
 *         }
 *     }
 *
 * @extends SharedRedisCache<WarehouseMetadataArray>
 */
class SharedTenantCache extends SharedRedisCache
{
    /**
     * @return WarehouseMetadataArray|null
     */
    public function read(string $key): ?array
    {
        return $this->getItem($key);
    }

    /**
     * @param WarehouseMetadataArray $tenant
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

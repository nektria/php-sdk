<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\WarehouseSharedInfo;
use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<WarehouseSharedInfo>
 */
class SharedWarehouseCache extends SharedRedisCache
{
    public function read(string $id): ?WarehouseSharedInfo
    {
        $data = $this->getItem($id);

        if ($data === null) {
            return null;
        }

        $this->save($data);

        return $data;
    }

    public function remove(string $id): void
    {
        $this->removeItem($id);
    }

    public function save(WarehouseSharedInfo $warehouse): void
    {
        $this->setItem(
            $warehouse->id,
            $warehouse,
            1209600,
        );
    }
}

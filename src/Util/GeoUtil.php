<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Document\WarehouseSharedInfo;

readonly class GeoUtil
{

    /**
     * @param WarehouseSharedInfo $warehouse
     * @param array{latitude: float, longitude: float} $from
     * @param array{latitude: float, longitude: float} $to
     * @return int distance in meters
     */
    public static function distance(WarehouseSharedInfo $warehouse, array $from, array $to): int
    {
        $dx = ($from['latitude'] - $to['latitude']) * $warehouse->latitudeFactor;
        $dy = ($from['longitude'] - $to['longitude']) * $warehouse->longitudeFactor;

        return (int) ceil(sqrt($dx * $dx + $dy * $dy));
    }
}

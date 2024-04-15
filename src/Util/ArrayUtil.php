<?php

declare(strict_types=1);

namespace Nektria\Util;

class ArrayUtil
{
    /**
     * @param string[] $new
     * @param string[] $old
     * @return array{
     *     added: string[],
     *     removed: string[],
     * }
     */
    public static function diff(array $new, array $old): array
    {
        return [
            'added' => array_diff($new, $old),
            'removed' => array_diff($old, $new),
        ];
    }

    /**
     * @template T
     * @param T[] $array1
     * @param T[] $array2
     * @return T[]
     */
    public static function commonItems(array $array1, array $array2): array
    {
        return array_intersect($array1, $array2);
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Util;

use IteratorAggregate;
use Nektria\Entity\EntityInterface;

class ArrayUtil
{
    /**
     * @template T
     * @param array<int, T>|IteratorAggregate<int, T> $list
     * @param callable(T): string $callback
     * @return array<string, T[]>
     */
    public static function classify(array | IteratorAggregate $list, callable $callback): array
    {
        $result = [];
        foreach ($list as $item) {
            $key = $callback($item);
            $result[$key] ??= [];
            $result[$key][] = $item;
        }

        return $result;
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
     * @param array<int,T>|IteratorAggregate<int, T> $list
     * @param callable(T): string $callback
     * @return array<string, T>
     */
    public static function mapify(array | IteratorAggregate $list, callable $callback, bool $keepFirst = false): array
    {
        $result = [];
        foreach ($list as $item) {
            $key = $callback($item);
            if (isset($result[$key]) && !$keepFirst) {
                continue;
            }

            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @template T of EntityInterface
     * @param T[] $list
     * @return array<string, T>
     */
    public static function mapifyEntities(array $list): array
    {
        return self::mapify($list, static fn (EntityInterface $item) => $item->id());
    }

    /**
     * @template T
     * @param T[] $list
     * @return T[]
     */
    public static function unique(array $list): array
    {
        return array_values(array_unique($list));
    }
}

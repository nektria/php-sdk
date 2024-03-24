<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<array{
 *     id: string,
 *     tenantId: string,
 *     role: string,
 *     warehouses: string[]
 * }>
 */
class SharedUserCache extends SharedRedisCache
{
    /**
     * @return array{
     *      id: string,
     *      tenantId: string,
     *      role: string,
     *      warehouses: string[]
     *  }|null
     */
    public function read(string $key): ?array
    {
        $data = $this->getItem($key);

        if ($data === null) {
            return null;
        }

        if ($data['id'] === $key) {
            return $data;
        }

        return $this->read($data['id']);
    }

    /**
     * @param array{
     *     id: string,
     *     tenantId: string,
     *     role: string,
     *     warehouses: string[]
     * } $user
     */
    public function save(string $key, array $user): void
    {
        $this->setItem(
            $key,
            $user,
            1209600
        );

        if ($key !== $user['id']) {
            $this->setItem(
                $user['id'],
                $user,
                1209600
            );
        }
    }
}

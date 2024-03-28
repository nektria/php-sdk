<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\User;
use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<array{
 *     id: string,
 *     tenantId: string,
 *     email: string,
 *     role: string,
 *     warehouses: string[],
 *     apiKey: string,
 *     dniNie: string|null,
 * }>
 */
class SharedUserCache extends SharedRedisCache
{
    public function __construct(
        private readonly SharedTenantCache $sharedTenantCache,
        string $redisDsn,
        string $env
    ) {
        parent::__construct($redisDsn, $env);
    }

    public function read(string $key): ?User
    {
        $data = $this->getItem($key);

        if ($data === null) {
            return null;
        }

        if ($data['id'] === $key) {
            $tenant = $this->sharedTenantCache->read($data['tenantId']);

            if ($tenant === null) {
                return null;
            }

            $user = new User(
                $data['id'],
                $data['email'],
                $data['warehouses'],
                $data['apiKey'],
                $data['role'],
                $tenant,
                $data['dniNie'],
            );

            $this->save($key, $user);

            return $user;
        }

        $user = $this->read($data['id']);

        if ($user !== null) {
            $this->save($key, $user);
        }

        return $user;
    }

    public function save(string $key, User $user): void
    {
        $data = [
            'id' => $user->id,
            'tenantId' => $user->tenant->id,
            'email' => $user->email,
            'role' => $user->role,
            'warehouses' => $user->warehouses,
            'apiKey' => $user->apiKey,
            'dniNie' => $user->dniNie,
        ];

        if ($key !== $user->id) {
            $this->setItem(
                $user->id,
                $data,
                1209600
            );
        }

        $this->setItem(
            $key,
            $data,
            1209600
        );
    }

    public function remove(string $id): void
    {
        $this->removeItem($id);
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Test;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Dto\TenantMetadata;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Service\ContextService;
use Nektria\Service\RoleManager;
use Nektria\Service\SharedUserCache;
use Nektria\Service\UserService;

class TestUserService extends UserService
{
    /** @var array<string, User> */
    private array $users = [];

    public function __construct(
        ContextService $contextService,
        SharedUserCache $sharedUserCache,
        RoleManager $roleManager
    ) {
        parent::__construct($contextService, $sharedUserCache, $roleManager);

        $tenant = new Tenant(
            id: '74a0c280-a76f-4231-aa85-97a20da592ab',
            name: 'Test',
            metadata: new TenantMetadata([])
        );

        $this->users['ak2000'] = new User(
            id: 'd4bd0258-2fe7-4599-9b9f-7c13fec85f69',
            email: '',
            warehouses: [],
            apiKey: '2000',
            role: RoleManager::ROLE_SYSTEM,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null
        );

        $this->users['ak2001'] = new User(
            id: 'ed1154d2-1d10-4ba1-b88e-c787611299aa',
            email: '',
            warehouses: [],
            apiKey: '2001',
            role: RoleManager::ROLE_API,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null
        );

        $this->users['ak1000'] = new User(
            id: '09e75ab6-2673-4ce8-a833-5a7cd284f831',
            email: 'admin@nektria.net',
            warehouses: [],
            apiKey: '1000',
            role: RoleManager::ROLE_ADMIN,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null
        );

        $this->users['ak1001'] = new User(
            id: 'cd3f0ff3-b3ce-4620-90ac-b8659a8779b5',
            email: 'user@nektria.net',
            warehouses: [],
            apiKey: '1001',
            role: RoleManager::ROLE_USER,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null
        );
    }

    public function authenticateApi(string $apiKey): void
    {
        dump('A');
        $this->clearAuthentication();

        $this->user = $this->users["ak{$apiKey}"] ?? null;

        if ($this->user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($this->user->tenantId);
        $this->contextService->setUserId($this->user->id);
    }

    public function authenticateSystem(string $tenantId): void
    {
        $this->clearAuthentication();

        $this->user = $this->users['ak2000'] ?? null;

        if ($this->user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($this->user->tenantId);
        $this->contextService->setUserId($this->user->id);
    }

    public function authenticateUser(string $apiKey): void
    {
        dump('B');
        $this->clearAuthentication();

        $this->user = $this->users["ak{$apiKey}"] ?? null;

        if ($this->user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($this->user->tenantId);
        $this->contextService->setUserId($this->user->id);
    }
}

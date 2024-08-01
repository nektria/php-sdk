<?php

declare(strict_types=1);

namespace Nektria\Test;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Dto\TenantMetadata;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Service\ContextService;
use Nektria\Service\RoleManager;
use Nektria\Service\SharedUserV2Cache;
use Nektria\Service\UserService;

class TestUserService extends UserService
{
    /** @var array<string, User> */
    private array $users = [];

    public function __construct(
        ContextService    $contextService,
        SharedUserV2Cache $sharedUserCache,
        RoleManager       $roleManager
    ) {
        parent::__construct($contextService, $sharedUserCache, $roleManager);

        $tenant = new Tenant(
            id: '74a0c280-a76f-4231-aa85-97a20da592ab',
            name: 'Test',
            metadata: new TenantMetadata([]),
        );

        $this->users['ak1000'] = new User(
            id: '09e75ab6-2673-4ce8-a833-5a7cd284f831',
            email: 'admin@nektria.net',
            name: 'Admin',
            warehouses: [],
            apiKey: '1000',
            role: RoleManager::ROLE_ADMIN,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $this->users['ak1001'] = new User(
            id: 'cd3f0ff3-b3ce-4620-90ac-b8659a8779b5',
            email: 'user@nektria.net',
            name: 'User',
            warehouses: [],
            apiKey: '1001',
            role: RoleManager::ROLE_USER,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $this->users['ak2000'] = new User(
            id: 'd4bd0258-2fe7-4599-9b9f-7c13fec85f69',
            email: '',
            name: '',
            warehouses: [],
            apiKey: '2000',
            role: RoleManager::ROLE_SYSTEM,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $this->users['ak2001'] = new User(
            id: 'ed1154d2-1d10-4ba1-b88e-c787611299aa',
            email: '',
            name: '',
            warehouses: [],
            apiKey: '2001',
            role: RoleManager::ROLE_API,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $tenant = new Tenant(
            id: '1aef7923-4b88-4d1f-b7b5-c409d962c60c',
            name: 'Test2',
            metadata: new TenantMetadata([]),
        );

        $this->users['ak3000'] = new User(
            id: 'f37c8deb-403d-4e1f-8f20-bd21f016449b',
            email: 'admin2@nektria.net',
            name: 'Admin2',
            warehouses: [],
            apiKey: '1001',
            role: RoleManager::ROLE_ADMIN,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $this->users['ak3001'] = new User(
            id: 'e662ccc1-024e-4e30-8968-685621f072a7',
            email: 'user2@nektria.net',
            name: 'User2',
            warehouses: [],
            apiKey: '1001',
            role: RoleManager::ROLE_USER,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $this->users['ak4000'] = new User(
            id: '4166b0f7-bd1a-4f81-aed7-7df972584390',
            email: '',
            name: '',
            warehouses: [],
            apiKey: '2000',
            role: RoleManager::ROLE_SYSTEM,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );

        $this->users['ak4001'] = new User(
            id: 'efd09992-7370-4ebe-a765-ef1806ba7584',
            email: '',
            name: '',
            warehouses: [],
            apiKey: '2001',
            role: RoleManager::ROLE_API,
            tenantId: $tenant->id,
            tenant: $tenant,
            dniNie: null,
        );
    }

    public function authenticateApi(string $apiKey): void
    {
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
        $this->clearAuthentication();

        $this->user = $this->users["ak{$apiKey}"] ?? null;

        if ($this->user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($this->user->tenantId);
        $this->contextService->setUserId($this->user->id);
    }
}

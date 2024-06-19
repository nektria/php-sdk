<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\UserServiceInterface;

class UserService implements UserServiceInterface
{
    protected ?User $user;

    public function __construct(
        protected readonly ContextService $contextService,
        protected readonly SharedUserCache $sharedUserCache,
        protected readonly RoleManager $roleManager,
    ) {
        $this->user = null;
    }

    public function authenticateApi(string $apiKey): void
    {
        $this->clearAuthentication();

        if ($apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $user = $this->sharedUserCache->read("API_{$apiKey}");
        if ($user === null) {
            $user = $this->sharedUserCache->read($apiKey);
        }

        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($user->tenantId);
        $this->contextService->setUserId($user->id);
    }

    public function authenticateSystem(string $tenantId): void
    {
        $this->clearAuthentication();

        $user = $this->sharedUserCache->read("SYSTEM_{$tenantId}");
        if ($user === null) {
            $user = $this->sharedUserCache->read("ADMIN_{$tenantId}");
        }

        $this->user = $user;

        if ($user === null || $user->apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($user->tenantId);
        $this->contextService->setUserId($user->id);
    }

    public function authenticateUser(string $apiKey): void
    {
        $this->clearAuthentication();

        if ($apiKey === '') {
            throw new InvalidAuthorizationException();
        }


        $user = $this->sharedUserCache->read($apiKey);
        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($user->tenantId);
        $this->contextService->setUserId($user->id);
    }

    public function clearAuthentication(): void
    {
        $this->contextService->setTenantId(null);
        $this->contextService->setUserId(null);
        $this->user = null;
    }

    public function retrieveUser(): User
    {
        if ($this->user === null) {
            throw new InvalidAuthorizationException();
        }

        return $this->user;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void
    {
        if ($this->user === null) {
            $this->roleManager->checkAtLeast(RoleManager::ROLE_ANY, $roles);
        } else {
            $this->roleManager->checkAtLeast($this->user->role, $roles);
        }
    }

    public function retrieveTenant(): Tenant
    {
        return $this->retrieveUser()->tenant;
    }
}

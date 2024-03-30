<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\UserServiceInterface;

class UserService implements UserServiceInterface
{
    private ?User $user;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly SharedUserCache $sharedUserCache,
        private readonly RoleManager $roleManager,
    ) {
        $this->user = null;
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

    public function authenticateSystem(string $tenantId): void
    {
        $this->clearAuthentication();

        $user = $this->sharedUserCache->read("SYSTEM_{$tenantId}");
        if ($user === null) {
            $user = $this->sharedUserCache->read("ADMIN_{$tenantId}");
        }
        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($user->tenantId);
        $this->contextService->setUserId($user->id);
    }

    public function authenticateUser(string $apiKey): void
    {
        $this->clearAuthentication();

        $user = $this->sharedUserCache->read($apiKey);
        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenantId($user->tenantId);
        $this->contextService->setUserId($user->id);
    }

    public function authenticateApi(string $apiKey): void
    {
        $this->clearAuthentication();

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

    public function clearAuthentication(): void
    {
        $this->contextService->setTenantId(null);
        $this->contextService->setUserId(null);
        $this->user = null;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function retrieveUser(): User
    {
        if ($this->user === null) {
            throw new InvalidAuthorizationException();
        }

        return $this->user;
    }

    public function retrieveTenant(): Tenant
    {
        return $this->retrieveUser()->tenant;
    }
}
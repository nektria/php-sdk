<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\User;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\UserServiceInterface;

class UserService implements UserServiceInterface
{
    private ?User $user;

    public function __construct(
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
        $user = $this->sharedUserCache->read("SYSTEM_{$tenantId}");
        if ($user === null) {
            $user = $this->sharedUserCache->read("ADMIN_{$tenantId}");
        }

        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }
    }

    public function authenticateUser(string $apiKey): void
    {
        $user = $this->sharedUserCache->read($apiKey);
        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }
    }

    public function authenticateApi(string $apiKey): void
    {
        $user = $this->sharedUserCache->read("API_{$apiKey}");
        if ($user === null) {
            $user = $this->sharedUserCache->read($apiKey);
        }

        $this->user = $user;

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }
    }

    public function clearAuthentication(): void
    {
        $this->user = null;
    }

    public function retrieveTenantName(): string
    {
        $name = $this->user?->tenant->name;

        if ($name === null) {
            throw new InvalidAuthorizationException();
        }

        return $name;
    }

    public function getTenantName(): ?string
    {
        return $this->user?->tenant->name;
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Dto\LocalClock;
use Nektria\Dto\UserContainer;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Infrastructure\SecurityServiceInterface;

class SecurityService implements SecurityServiceInterface
{
    protected readonly UserContainer $userContainer;

    public function __construct(
        protected readonly ContextService $contextService,
        protected readonly SharedUserV2Cache $sharedUserCache,
        protected readonly RoleManager $roleManager,
        private readonly YieldManagerClient $yieldManagerClient,
    ) {
        $this->userContainer = new UserContainer();
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

        $this->userContainer->setUser($user);

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenant($user->tenantId, $user->tenant->name);
        $this->contextService->setUserId($user->id);
        LocalClock::defaultTimezone($user->tenant->timezone);
    }

    public function authenticateSystem(string $tenantId): void
    {
        $this->clearAuthentication();

        $user = $this->sharedUserCache->read("SYSTEM_{$tenantId}");
        if ($user === null) {
            $user = $this->sharedUserCache->read("ADMIN_{$tenantId}");
        }

        $this->userContainer->setUser($user);

        if ($user === null || $user->apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenant($user->tenantId, $user->tenant->name);
        $this->contextService->setUserId($user->id);
        LocalClock::defaultTimezone($user->tenant->timezone);
    }

    public function authenticateUser(string $apiKey): void
    {
        $this->clearAuthentication();

        if ($apiKey === '') {
            throw new InvalidAuthorizationException();
        }

        $user = $this->sharedUserCache->read($apiKey);
        $this->userContainer->setUser($user);

        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        $this->contextService->setTenant($user->tenantId, $user->tenant->name);
        $this->contextService->setUserId($user->id);
        LocalClock::defaultTimezone($user->tenant->timezone);
    }

    public function clearAuthentication(): void
    {
        $this->contextService->setTenant(null, null);
        $this->contextService->setUserId(null);
        LocalClock::defaultTimezone('UTC');
        $this->userContainer->setUser(null);
    }

    public function retrieve(string $id): User
    {
        $user = $this->sharedUserCache->read($id);

        if ($user === null && $this->contextService->project() !== 'yieldmanager') {
            $user = $this->yieldManagerClient->getUser($id);
        }

        if ($user === null) {
            throw new ResourceNotFoundException('User', $id);
        }

        return $user;
    }

    public function retrieveTenant(): Tenant
    {
        return $this->retrieveUser()->tenant;
    }

    public function retrieveUser(): User
    {
        $user = $this->user();
        if ($user === null) {
            throw new InvalidAuthorizationException();
        }

        return $user;
    }

    public function user(): ?User
    {
        return $this->userContainer->user();
    }

    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void
    {
        if ($this->user() === null) {
            $this->roleManager->checkAtLeast(RoleManager::ROLE_ANY, $roles);
        } else {
            $this->roleManager->checkAtLeast($this->user()->role, $roles);
        }
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Message;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Service\RoleManager;
use Nektria\Util\ContainerBox;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function count;
use function in_array;

readonly abstract class MessageHandler
{
    private ContainerBox $containerBox;

    public function __construct()
    {
        $this->containerBox = new ContainerBox();
    }

    public function inject(ContainerInterface $container): void
    {
        $this->containerBox->set($container);
    }

    protected function checkAccessToWarehouse(string $warehouseId): void
    {
        if ($this->hasAccessToWarehouse($warehouseId)) {
            throw new InsufficientCredentialsException();
        }
    }

    protected function hasAccessToWarehouse(string $warehouseId): bool
    {
        $user = $this->userService()->retrieveUser();

        if ($user->role === RoleManager::ROLE_DRIVER && count($user->warehouses) === 0) {
            return false;
        }

        if (count($user->warehouses) === 0) {
            return true;
        }

        try {
            if ($this->roleManager()->checkAtLeast($user->role, [RoleManager::ROLE_ADMIN])) {
                return true;
            }
        } catch (InsufficientCredentialsException) {
        }

        if (!in_array($warehouseId, $user->warehouses, true)) {
            return false;
        }

        return true;
    }

    protected function tenant(): Tenant
    {
        return $this->userService()->retrieveUser()->tenant;
    }

    protected function tenantId(): string
    {
        return $this->userService()->retrieveUser()->tenantId;
    }

    protected function user(): User
    {
        return $this->userService()->retrieveUser();
    }

    protected function userService(): UserServiceInterface
    {
        /** @var UserServiceInterface $service */
        $service = $this->containerBox->get()->get(UserServiceInterface::class);

        return $service;
    }

    private function roleManager(): RoleManager
    {
        /** @var RoleManager $service */
        $service = $this->containerBox->get()->get(RoleManager::class);

        return $service;
    }
}

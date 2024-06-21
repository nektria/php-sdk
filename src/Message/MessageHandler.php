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
use function define;
use function in_array;

define('MESSAGE_HANDLER_CONTAINER_BOX', new ContainerBox());

readonly abstract class MessageHandler
{
    public const ContainerBox CONTAINER_BOX = MESSAGE_HANDLER_CONTAINER_BOX;

    public function inject(ContainerInterface $container): void
    {
        self::CONTAINER_BOX->set($container);
    }

    protected function checkAccessToTenant(string $tenantId): void
    {
        if ($tenantId !== $this->tenant()->id) {
            throw new InsufficientCredentialsException();
        }
    }

    protected function checkAccessToWarehouse(string $warehouseId): void
    {
        if (!$this->hasAccessToWarehouse($warehouseId)) {
            throw new InsufficientCredentialsException();
        }
    }

    protected function containerBox(): ContainerBox
    {
        return self::CONTAINER_BOX;
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
        $service = self::CONTAINER_BOX->get()->get(UserServiceInterface::class);

        return $service;
    }

    private function roleManager(): RoleManager
    {
        /** @var RoleManager $service */
        $service = self::CONTAINER_BOX->get()->get(RoleManager::class);

        return $service;
    }
}

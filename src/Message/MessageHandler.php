<?php

declare(strict_types=1);

namespace Nektria\Message;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Service\ProcessRegistry;
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
        $user = $this->userService()->retrieveCurrentUser();

        if ($user->role === RoleManager::ROLE_DRIVER && count($user->warehouses) === 0) {
            return false;
        }

        if (count($user->warehouses) === 0) {
            return true;
        }

        if ($this->roleManager()->canAtLeast($user->role, [RoleManager::ROLE_ADMIN])) {
            return true;
        }

        if (!in_array($warehouseId, $user->warehouses, true)) {
            return false;
        }

        return true;
    }

    protected function registry(): ProcessRegistry
    {
        /** @var ProcessRegistry $service */
        $service = self::CONTAINER_BOX->get()->get(ProcessRegistry::class);

        return $service;
    }

    protected function tenant(): Tenant
    {
        return $this->userService()->retrieveCurrentUser()->tenant;
    }

    protected function tenantId(): string
    {
        return $this->userService()->retrieveCurrentUser()->tenantId;
    }

    protected function user(): User
    {
        return $this->userService()->retrieveCurrentUser();
    }

    protected function userService(): SecurityServiceInterface
    {
        /** @var SecurityServiceInterface $service */
        $service = self::CONTAINER_BOX->get()->get(SecurityServiceInterface::class);

        return $service;
    }

    private function roleManager(): RoleManager
    {
        /** @var RoleManager $service */
        $service = self::CONTAINER_BOX->get()->get(RoleManager::class);

        return $service;
    }
}

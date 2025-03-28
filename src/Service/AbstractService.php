<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\ServiceContainer;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\SecurityServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly abstract class AbstractService
{
    protected ServiceContainer $serviceContainer;

    public function __construct()
    {
        $this->serviceContainer = new ServiceContainer();
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->serviceContainer->setContainer($container);
    }

    protected function alertService(): AlertService
    {
        /** @var AlertService $service */
        $service = $this->serviceContainer->container()->get(AlertService::class);

        return $service;
    }

    protected function bus(): BusInterface
    {
        /** @var BusInterface $service */
        $service = $this->serviceContainer->container()->get(BusInterface::class);

        return $service;
    }

    protected function compassClient(): CompassClient
    {
        /** @var CompassClient $service */
        $service = $this->serviceContainer->container()->get(CompassClient::class);

        return $service;
    }

    protected function container(): ContainerInterface
    {
        return $this->serviceContainer->container();
    }

    protected function contextService(): ContextService
    {
        /** @var ContextService $service */
        $service = $this->serviceContainer->container()->get(ContextService::class);

        return $service;
    }

    protected function googleClient(): GoogleClient
    {
        /** @var GoogleClient $service */
        $service = $this->serviceContainer->container()->get(GoogleClient::class);

        return $service;
    }

    protected function logService(): LogService
    {
        /** @var LogService $service */
        $service = $this->serviceContainer->container()->get(LogService::class);

        return $service;
    }

    protected function registry(): ProcessRegistry
    {
        /** @var ProcessRegistry $service */
        $service = $this->serviceContainer->container()->get(ProcessRegistry::class);

        return $service;
    }

    protected function requestClient(): RequestClient
    {
        /** @var RequestClient $service */
        $service = $this->serviceContainer->container()->get(RequestClient::class);

        return $service;
    }

    protected function roleManager(): RoleManager
    {
        /** @var RoleManager $service */
        $service = $this->serviceContainer->container()->get(RoleManager::class);

        return $service;
    }

    protected function routemanagerClient(): RoutemanagerClient
    {
        /** @var RoutemanagerClient $service */
        $service = $this->serviceContainer->container()->get(RoutemanagerClient::class);

        return $service;
    }

    protected function securityService(): SecurityServiceInterface
    {
        /** @var SecurityServiceInterface $service */
        $service = $this->serviceContainer->container()->get(SecurityServiceInterface::class);

        return $service;
    }

    protected function socketService(): SocketService
    {
        /** @var SocketService $service */
        $service = $this->serviceContainer->container()->get(SocketService::class);

        return $service;
    }

    protected function yieldmanagerClient(): YieldmanagerClient
    {
        /** @var YieldmanagerClient $service */
        $service = $this->serviceContainer->container()->get(YieldmanagerClient::class);

        return $service;
    }
}

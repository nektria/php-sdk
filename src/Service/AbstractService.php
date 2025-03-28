<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\ServiceContainer;
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

    protected function logService(): LogService
    {
        /** @var LogService $service */
        $service = $this->serviceContainer->container()->get(LogService::class);

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

    protected function securityService(): SecurityServiceInterface
    {
        /** @var SecurityServiceInterface $service */
        $service = $this->serviceContainer->container()->get(SecurityServiceInterface::class);

        return $service;
    }
}

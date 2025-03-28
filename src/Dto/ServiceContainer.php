<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Exception\NektriaException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServiceContainer
{
    private ?ContainerInterface $container;

    public function __construct()
    {
        $this->container = null;
    }

    public function container(): ContainerInterface
    {
        if ($this->container === null) {
            throw new NektriaException('Container not set');
        }

        return $this->container;
    }

    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }
}

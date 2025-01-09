<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Exception\NektriaException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerBox
{
    public ?ContainerInterface $container;

    public function __construct()
    {
        $this->container = null;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class): object
    {
        if ($this->container === null) {
            throw new NektriaException('Container not set.');
        }

        /** @var T $service */
        $service = $this->container->get($class);

        return $service;
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Exception\NektriaException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerBox
{
    private ?ContainerInterface $container = null;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class): object
    {
        if ($this->container === null) {
            throw new NektriaException('E_500', 'Container not set.');
        }

        /** @var T $service */
        $service = $this->container->get($class);

        return $service;
    }

    public function set(?ContainerInterface $container): void
    {
        $this->container = $container;
    }
}

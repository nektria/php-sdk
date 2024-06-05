<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Exception\NektriaException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerBox
{
    private ?ContainerInterface $container = null;

    public function get(): ContainerInterface
    {
        if ($this->container === null) {
            throw new NektriaException('Container not initialized');
        }

        return $this->container;
    }

    public function set(?ContainerInterface $container): void
    {
        $this->container = $container;
    }
}

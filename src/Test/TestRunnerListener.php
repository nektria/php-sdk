<?php

declare(strict_types=1);

namespace Nektria\Test;

use Symfony\Component\DependencyInjection\ContainerInterface;

readonly class TestRunnerListener
{
    public function __construct(
        protected ContainerInterface $container
    ) {
    }

    public function onBoot(): void
    {
    }
}

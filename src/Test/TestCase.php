<?php

declare(strict_types=1);

namespace Nektria\Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestCase extends WebTestCase
{
    protected KernelBrowser $client;

    private static bool $onBootExecuted = false;

    public function init(): void
    {
        if (!self::$booted) {
            $this->client = self::createClient();

            /** @var TestRunnerListener $runnerListener */
            $runnerListener = self::getContainer()->get(TestRunnerListener::class);

            if (!self::$onBootExecuted) {
                $runnerListener->onBoot();

                $methods = get_class_methods($this);
                foreach ($methods as $method) {
                    if (str_starts_with($method, 'init')) {
                        // @phpstan-ignore-next-line
                        $this->$method();
                    }
                }
            }

            // self::$onBootExecuted = true;
        }
    }
}

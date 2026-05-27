<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Nektria\Util\ContainerBoxPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Nektria\Service\AlertService;

$container = new ContainerBuilder();

// Mock dependencies for AlertService constructor
$definition = new Definition(AlertService::class, [
    new Symfony\Component\DependencyInjection\Reference('shared_discord_cache'),
    'alerts_token'
]);
$container->setDefinition(AlertService::class, $definition);

// Create a mock shared_discord_cache service
$container->register('shared_discord_cache', \stdClass::class);

$pass = new ContainerBoxPass();
$pass->process($container);

$calls = $container->getDefinition(AlertService::class)->getMethodCalls();
$found = false;
foreach ($calls as $call) {
    if ($call[0] === 'setContainer') {
        $found = true;
        break;
    }
}

if ($found) {
    echo "SUCCESS: setContainer call added to AlertService definition.\n";
} else {
    echo "FAILURE: setContainer call NOT added to AlertService definition.\n";
    exit(1);
}

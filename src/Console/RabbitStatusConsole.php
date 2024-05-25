<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Service\RequestClient;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function is_string;

class RabbitStatusConsole extends Console
{
    public function __construct(
        private readonly RequestClient $requestClient,
        private readonly ContainerInterface $container,
    ) {
        parent::__construct('sdk:rabbit:status');
    }

    protected function play(): void
    {
        if (!$this->container->hasParameter('rabbitDsn')) {
            $this->output()->writeln('Rabbit not configured.');
            return;
        }

        $rabbitDsn = $this->container->getParameter('rabbitDsn');
        if (!is_string($rabbitDsn)) {
            return;
        }
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        $content = $this->requestClient->get(
            "{$host}/api/queues",
        )->json();

        foreach ($content as $queue) {
            $name = str_pad($queue['name'], 35);
            $vhost = $queue['vhost'];
            $ready = str_pad((string) $queue['messages_ready'], 6);
            $unacked = str_pad((string) $queue['messages_unacknowledged'], 6);
            $speed = $queue['messages_unacknowledged_details']['rate'];

            $this->output()->writeln("{$vhost} {$name}: R:{$ready} U:{$unacked} S:{$speed}");
        }
    }
}

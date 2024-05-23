<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Service\RequestClient;

class RabbitQueueDeleteConsole extends Console
{
    public function __construct(
        private readonly RequestClient $requestClient,
        private readonly string $rabbitDsn
    ) {
        parent::__construct('sdk:rabbit:delete');
    }

    protected function configure(): void
    {
        $this->addArgument('queue');
    }

    protected function play(): void
    {
        $this->output()->writeln('Starting queue deletion...');
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $this->rabbitDsn);
        $targetQueue = $this->input()->getArgument('queue');
        $content = $this->requestClient->get("{$host}/api/queues")->json();
        $this->output()->writeln('Purging ' . $this->input()->getArgument('queue'));

        foreach ($content as $queue) {
            $name = $queue['name'];

            if ($name === $targetQueue) {
                $vhost = urlencode($queue['vhost']);
                $this->output()->writeln("Deleting queue '{$name}'...");
                $this->requestClient->delete(
                    "{$host}/api/queues/{$vhost}/{$name}",
                    data: [
                        'vhost' => '/',
                        'name' => $name,
                        'mode' => 'delete',
                    ]
                );
                $this->output()->writeln("Queue '{$name}' deleted.");

                break;
            }
        }
    }
}

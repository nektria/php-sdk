<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Service\RequestClient;
use Nektria\Util\Controller\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function is_string;

#[Route('/api/admin/rabbit')]
readonly class RabbitController extends Controller
{
    #[Route('')]
    public function getStatus(
        ContainerInterface $container,
        RequestClient $requestClient,
    ): DocumentResponse {
        if (!$container->hasParameter('rabbitDsn')) {
            return $this->emptyResponse();
        }

        $rabbitDsn = $container->getParameter('rabbitDsn');
        if (!is_string($rabbitDsn)) {
            return $this->emptyResponse();
        }
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        $content = $requestClient->get(
            "{$host}/api/queues",
        )->json();

        $data = [];

        foreach ($content as $queue) {
            $name = str_pad($queue['name'], 35);
            $vhost = $queue['vhost'];
            $ready = str_pad((string) $queue['messages_ready'], 6);
            $unacked = str_pad((string) $queue['messages_unacknowledged'], 6);
            $speed = $queue['messages_unacknowledged_details']['rate'];

            $data[] = [
                'vhost' => $vhost,
                'name' => $name,
                'ready' => $ready,
                'unacked' => $unacked,
                'speed' => $speed,
            ];
        }

        return $this->documentResponse(new ArrayDocument($data));
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentCollection;
use Nektria\Document\DocumentResponse;
use Nektria\Service\RequestClient;
use Nektria\Util\Controller\Route;
use Nektria\Util\StringUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function is_string;

#[Route('/api/admin/rabbit')]
readonly class RabbitController extends Controller
{
    #[Route('', method: 'GET')]
    public function getStatus(
        ContainerInterface $container,
        RequestClient      $requestClient,
    ): DocumentResponse
    {
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
            $name = StringUtil::trim($queue['name']);
            $ready = (int)$queue['messages_ready'];
            $unacked = (int)$queue['messages_unacknowledged'];
            $speed = (float)$queue['messages_unacknowledged_details']['rate'];

            $data[] = new ArrayDocument([
                'queues' => [
                    'name' => $name,
                    'ready' => $ready,
                    'unacknowledged' => $unacked,
                    'rate' => $speed,
                ]
            ]);
        }

        return $this->documentResponse(new DocumentCollection($data));
    }
}

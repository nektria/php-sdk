<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Infrastructure\SharedVariableCache;
use Nektria\Service\RequestClient;
use Nektria\Util\Controller\Route;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function is_string;

#[Route('/api/admin/rabbit')]
readonly class RabbitController extends Controller
{
    #[Route('', method: 'GET')]
    public function getStatus(
        SharedVariableCache $sharedVariableCache,
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

        $messages = $this->getPendingMessages($sharedVariableCache, []);
        $messages = $this->getProcessingMessages($sharedVariableCache, $messages);

        $data = [
            'queues' => [],
            'messages' => $messages,
        ];

        foreach ($content as $queue) {
            $name = StringUtil::trim($queue['name']);
            $ready = (int) $queue['messages_ready'];
            $unacked = (int) $queue['messages_unacknowledged'];
            $speed = (float) $queue['messages_unacknowledged_details']['rate'];

            $data['queues'][] = [
                'name' => $name,
                'ready' => $ready,
                'unacknowledged' => $unacked,
                'rate' => $speed,
            ];
        }

        return $this->documentResponse(new ArrayDocument($data));
    }

    /**
     * @param array<string, array<string, number>> $result
     * @return array<string, array<string, number>>
     */
    private function getPendingMessages(SharedVariableCache $sharedVariableCache, array $result): array
    {
        $data = JsonUtil::decode($sharedVariableCache->readString('bus_messages_pending', '[]'));

        $projects = [];
        foreach ($data as $item) {
            [$project, $clzz] = explode('_', $item);

            $projects[$project] ??= [];
            $projects[$project][] = $clzz;
        }

        ksort($projects);
        foreach ($projects as $project => $clzzs) {
            sort($clzzs);
            foreach ($clzzs as $clzz) {
                $times = $sharedVariableCache->readInt("bus_messages_pending_{$project}_{$clzz}");
                if ($times > 0) {
                    $result[$project] ??= [];
                    $result[$project][$clzz] ??= 0;
                    $result[$project][$clzz] += $times;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, number>> $result
     * @return array<string, array<string, number>>
     */
    private function getProcessingMessages(SharedVariableCache $sharedVariableCache, array $result): array
    {
        $data = JsonUtil::decode($sharedVariableCache->readString('bus_messages', '[]'));

        $projects = [];
        foreach ($data as $item) {
            [$project, $clzz] = explode('_', $item);

            $projects[$project] ??= [];
            $projects[$project][] = $clzz;
        }

        ksort($projects);
        foreach ($projects as $project => $clzzs) {
            sort($clzzs);
            foreach ($clzzs as $clzz) {
                $times = $sharedVariableCache->readInt("bus_messages_{$project}_{$clzz}");
                if ($times > 0) {
                    $result[$project] ??= [];
                    $result[$project][$clzz] ??= 0;
                    $result[$project][$clzz] += $times;
                }
            }
        }

        return $result;
    }
}

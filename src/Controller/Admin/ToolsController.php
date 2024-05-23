<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Service\ContextService;
use Nektria\Service\RequestClient;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

use const STR_PAD_LEFT;

#[Route('/api/admin/tools')]
class ToolsController extends Controller
{
    #[Route('/console', methods: ['PATCH'])]
    public function console(): Response
    {
        $command = $this->requestData->retrieveString('command');
        if (!str_starts_with($command, 'admin:') || str_starts_with($command, 'sdk:')) {
            throw new InsufficientCredentialsException();
        }

        $args = $this->requestData->getArray('args') ?? [];
        $args[] = '--clean';
        $command = new Process(array_merge(['../bin/console', $command], $args));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/crypt', methods: 'GET')]
    public function crypt(ContextService $contextService): Response
    {
        $lines = explode("\n", FileUtil::read('/app/.env'));
        $envs = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            $parts = explode('=', $line);
            $env = $parts[0];
            $envs[$env] = getenv()[$env] ?? $parts[1];
        }

        $result = openssl_encrypt(
            base64_encode(JsonUtil::encode($envs)),
            'AES-256-CBC',
            $contextService->project(),
            0,
            str_pad($contextService->project(), 16, '0', STR_PAD_LEFT),
        );

        return new Response((string) $result);
    }

    #[Route('/database/migrations', methods: ['GET'])]
    public function databaseMigrations(): Response
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:migration:status']));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/database/read', methods: 'GET')]
    public function databaseRead(ArrayDocumentReadModel $readModel): DocumentResponse
    {
        return $this->documentResponse(
            $readModel->readCustom(
                $this->requestData->retrieveString('table'),
                $this->requestData->retrieveString('orderBy'),
                $this->requestData->getInt('page') ?? 1,
            ),
        );
    }

    #[Route('/database/schema', methods: ['GET'])]
    public function databaseSchema(): Response
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:schema:update', '--dump-sql']));
        $command->run();

        return $this->buildResponseForProcess($command);
    }

    #[Route('/debug', methods: ['PATCH'])]
    public function debug(ContextService $contextService): JsonResponse
    {
        $enable = $this->requestData->retrieveBool('enable');
        $lifetime = $this->requestData->getInt('lifetime') ?? 3600;
        $projects = $this->requestData->retrieveStringArray('projects');

        $contextService->setDebugMode($enable, $projects, $lifetime);

        return $this->emptyResponse();
    }

    #[Route('/debug/status', methods: ['PATCH'])]
    public function debugStatus(ContextService $contextService): JsonResponse
    {
        return new JsonResponse($contextService->debugModes(
            $this->requestData->retrieveStringArray('projects'),
        ));
    }

    #[Route('/decrypt', methods: 'PATCH')]
    public function decrypt(ContextService $contextService): JsonResponse
    {
        if (!$contextService->isLocalEnvironament()) {
            throw new InsufficientCredentialsException();
        }

        $pass = $this->requestData->getString('pass') ?? $contextService->project();

        return $this->documentResponse(new ArrayDocument(JsonUtil::decode(
            (string) base64_decode(
                (string) openssl_decrypt(
                    $this->requestData->retrieveString('hash'),
                    'AES-256-CBC',
                    $pass,
                    0,
                    str_pad($pass, 16, '0', STR_PAD_LEFT),
                ),
                true
            )
        )));
    }

    #[Route('/rabbit/delete', methods: 'PATCH')]
    public function rabbitDelete(RequestClient $requestClient, string $rabbitDsn): DocumentResponse
    {
        $queue = $this->requestData->retrieveString('queue');
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        $content = $requestClient->get("{$host}/api/queues/%2F/{$queue}")->json();

        $vhost = urlencode($content['vhost']);

        $requestClient->delete(
            "{$host}/api/queues/{$vhost}/{$queue}",
            data: [
                'vhost' => '/',
                'name' => $queue,
                'mode' => 'delete',
            ]
        );

        return $this->emptyResponse();
    }

    #[Route('/rabbit/status', methods: 'GET')]
    public function rabbitStatus(RequestClient $requestClient, string $rabbitDsn): DocumentResponse
    {
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        $content = $requestClient->get(
            "{$host}/api/queues",
        )->json();

        $data = [];
        foreach ($content as $queue) {
            $name = str_pad($queue['name'], 35);
            $ready = str_pad((string) $queue['messages_ready'], 6);
            $unacked = str_pad((string) $queue['messages_unacknowledged'], 6);
            $speed = $queue['messages_unacknowledged_details']['rate'];

            $data[$name] = [
                'ready' => $ready,
                'unacknowledged' => $unacked,
                'speed' => $speed,
            ];
        }

        return $this->documentResponse(new ArrayDocument($data));
    }

    private function buildResponseForProcess(Process $process): Response
    {
        $result = $process->getExitCode() === 0 ? "Sucessful\n\n" : "Failure\n\n";
        $result .= "OUTPUT\n\n";
        $result .= $process->getOutput() . "\n\n";
        $result .= "ERROR\n\n";
        $result .= $process->getErrorOutput() . "\n\n";

        return new Response($result, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}

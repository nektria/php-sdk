<?php

declare(strict_types=1);

namespace Nektria\Controller\Admin;

use Nektria\Controller\Controller;
use Nektria\Document\DatabaseValue;
use Nektria\Document\DocumentResponse;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\DatabaseValueReadModel;
use Nektria\Service\ContextService;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

use const PHP_EOL;
use const STR_PAD_LEFT;

#[Route('/api/admin/tools')]
class ToolsController extends Controller
{
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
    public function databaseMigrations(): JsonResponse
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:migration:status']));
        $command->run();

        return new JsonResponse([
            'status' => $command->getExitCode(),
            'output' => $this->cleanOutput($command->getOutput()),
            'errorOutput' => $this->cleanOutput($command->getErrorOutput()),
        ]);
    }

    #[Route('/database/read', methods: 'GET')]
    public function databaseRead(DatabaseValueReadModel $readModel): DocumentResponse
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
    public function databaseSchema(): JsonResponse
    {
        $command = new Process(array_merge(['../bin/console', 'doctrine:schema:update', '--dump-sql']));
        $command->run();

        return new JsonResponse([
            'status' => $command->getExitCode(),
            'output' => $this->cleanOutput($command->getOutput()),
            'errorOutput' => $this->cleanOutput($command->getErrorOutput()),
        ]);
    }

    #[Route('/decrypt', methods: 'PATCH')]
    public function decrypt(ContextService $contextService): JsonResponse
    {
        if (!$contextService->isLocalEnvironament()) {
            throw new InsufficientCredentialsException();
        }

        $pass = $this->requestData->getString('pass') ?? $contextService->project();

        return $this->documentResponse(new DatabaseValue(JsonUtil::decode(
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

    /**
     * @return string[]
     */
    private function cleanOutput(string $message): array
    {
        $parts = explode(PHP_EOL, $message);
        $ret = [];

        foreach ($parts as $part) {
            $ret[] = StringUtil::trim($part);
        }

        return $ret;
    }
}

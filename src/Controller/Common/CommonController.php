<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Document\DocumentResponse;
use Nektria\Dto\Clock;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Service\ContextService;
use Nektria\Service\HealthService;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use function count;

readonly class CommonController extends Controller
{
    #[Route('/healthz', methods: 'GET')]
    public function health(HealthService $healthService): DocumentResponse
    {
        $data = $healthService->check();
        ksort($data['results']);

        return $this->documentResponse(
            new ArrayDocument($data),
            count($data['errors']) === 0 ? DocumentResponse::HTTP_OK : DocumentResponse::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    #[Route('/ping', methods: 'GET')]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['response' => 'pong']);
    }

    #[Route('/version', methods: 'GET')]
    public function version(ContainerInterface $container, ContextService $contextService): JsonResponse
    {
        if ($container->has(ArrayDocumentReadModel::class)) {
            /** @var ArrayDocumentReadModel $readModel */
            $readModel = $container->get(ArrayDocumentReadModel::class);
            $versions = $readModel->readCustom('doctrine_migration_versions', 'version', 1);
            $migration = $versions->first()->data ?? ['version' => 'DoctrineMigrations\\none'];

            $migrationVersion = explode('\\', $migration['version'])[1];
        } else {
            $migrationVersion = null;
        }

        try {
            $versionFile = JsonUtil::decode(FileUtil::read('/app/NK_VERSION'));
        } catch (Throwable) {
            $versionFile = [
                'builtAt' => Clock::now()->dateTimeString('Europe/Madrid'),
                'hash' => '',
                'project' => $contextService->project(),
                'type' => 'Development',
                'version' => '',
            ];
        }
        $versionFile['migration'] = $migrationVersion;

        return $this->documentResponse(new ArrayDocument($versionFile));
    }
}

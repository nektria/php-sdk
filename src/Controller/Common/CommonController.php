<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Dto\Clock;
use Nektria\Infrastructure\ArrayDocumentModel;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class CommonController extends Controller
{
    #[Route('/ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['response' => 'pong']);
    }

    #[Route('/version', methods: 'GET')]
    public function version(ContainerInterface $container): JsonResponse
    {
        if ($container->has(ArrayDocumentModel::class)) {
            /** @var ArrayDocumentModel $readModel */
            $readModel = $container->get(ArrayDocumentModel::class);
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
                'builtAt' => Clock::now()->dateTimeString(),
                'hash' => '',
                'type' => 'Development',
                'version' => '',
            ];
        }
        $versionFile['migration'] = $migrationVersion;

        return $this->documentResponse(new ArrayDocument($versionFile));
    }
}

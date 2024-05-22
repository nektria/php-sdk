<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Nektria\Document\ArrayDocument;
use Nektria\Dto\Clock;
use Nektria\Infrastructure\DatabaseValueReadModel;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
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
    public function version(DatabaseValueReadModel $readModel): JsonResponse
    {
        $versions = $readModel->readCustom('doctrine_migration_versions', 'version', 1);
        $migration = $versions->first()->data ?? ['version' => 'DoctrineMigrations\\none'];

        try {
            $versionFile = JsonUtil::decode(FileUtil::read('/app/NK_VERSION'));
        } catch (Throwable) {
            $versionFile = [
                'createdAt' => Clock::now()->dateTimeString(),
                'hash' => '',
                'type' => '',
                'version' => '',
            ];
        }

        $versionFile['migration'] = explode('\\', $migration['version'])[1];

        return $this->documentResponse(new ArrayDocument($versionFile));
    }
}

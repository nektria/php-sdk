<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PingController extends Controller
{
    #[Route('/ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['response' => 'pong.']);
    }
}

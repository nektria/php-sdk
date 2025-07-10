<?php

declare(strict_types=1);

namespace Nektria\Listener;

use DomainException;
use Nektria\Document\DocumentCollection;
use Nektria\Document\DocumentResponse;
use Nektria\Document\FileDocument;
use Nektria\Document\Tenant;
use Nektria\Document\ThrowableDocument;
use Nektria\Document\User;
use Nektria\Dto\Clock;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Infrastructure\SharedTemporalConsumptionCache;
use Nektria\Infrastructure\VariableCache;
use Nektria\Service\AlertService;
use Nektria\Service\Bus;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Nektria\Service\ProcessRegistry;
use Nektria\Service\RoleManager;
use Nektria\Util\JsonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

use function in_array;

abstract class RequestListener extends BaseRequestListener
{

    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        AlertService                              $alertService,
        Bus                                       $bus,
        ContextService                            $contextService,
        LogService                                $logService,
        SharedTemporalConsumptionCache            $temporalConsumptionCache,
        VariableCache                             $variableCache,
        ProcessRegistry                           $processRegistry,
        ContainerInterface                        $container
    )
    {
        parent::__construct(
            alertService: $alertService,
            bus: $bus,
            contextService: $contextService,
            logService: $logService,
            temporalConsumptionCache: $temporalConsumptionCache,
            variableCache: $variableCache,
            processRegistry: $processRegistry,
            container: $container
        );
    }

    protected function assignLogLevel(Request $request): ?string
    {
        $route = $request->attributes->get('_route') ?? '';

        if (
            str_starts_with($route, 'nektria_')
            || str_starts_with($route, 'app_admin_tools_')
            || str_starts_with($route, 'app_common_')
        ) {
            return self::LOG_LEVEL_NONE;
        }

        return self::LOG_LEVEL_INFO;
    }

    protected function checkAccess(Request $request): void
    {
        $route = $request->attributes->get('_route') ?? '';
        $apiKey = $this->readApiKey($request);

        if (str_starts_with($route, 'app_common_') || str_starts_with($route, 'nektria_common_')) {
            if ($apiKey !== '') {
                $this->securityService->authenticateUser($apiKey);

                if (!$this->validateUser($this->securityService->retrieveCurrentUser())) {
                    $this->securityService->clearAuthentication();

                    throw new InvalidAuthorizationException();
                }
            }

            return;
        }

        if (str_starts_with($route, 'app_admin') || str_starts_with($route, 'nektria_admin')) {
            $this->contextService->setContext(ContextService::ADMIN);
            $this->securityService->authenticateUser($apiKey);

            try {
                $this->securityService->validateRole([RoleManager::ROLE_ADMIN]);
            } catch (InsufficientCredentialsException) {
                $this->securityService->clearAuthentication();
            }
        } elseif (
            str_starts_with($route, 'app_apilegacy_')
            || str_starts_with($route, 'app_api_')
            || str_starts_with($route, 'nektria_api_')
        ) {
            $this->contextService->setContext(ContextService::PUBLIC);
            $this->securityService->authenticateUser($apiKey);
        } elseif (str_starts_with($route, 'app_api2_') || str_starts_with($route, 'nektria_api2_')) {
            $this->contextService->setContext(ContextService::PUBLIC_V2);
            $this->securityService->authenticateUser($apiKey);
        } elseif (str_starts_with($route, 'app_web_') || str_starts_with($route, 'nektria_web_')) {
            $this->contextService->setContext(ContextService::INTERNAL);
            $this->securityService->authenticateUser($apiKey);
            if ($this->securityService->currentUser() !== null) {
                $this->contextService->addExtra('userId', $this->securityService->currentUser()->id);
            }
        }

        if (!$this->validateUser($this->securityService->retrieveCurrentUser())) {
            $this->securityService->clearAuthentication();

            throw new InvalidAuthorizationException();
        }
    }

    /**
     * @return string[]
     */
    protected function exposedHeaders(): array
    {
        return [
            'Link', 'X-Authorization'
        ];
    }

    /**
     * @return string[]
     */
    protected function getAllowedCorsHeaders(): array
    {
        return [
            'Accept', 'Accept-Encoding', 'Accept-Language', 'Access-Control-Request-Headers',
            'Access-Control-Request-Method', 'Connection', 'Content-Length', 'Content-Type', 'Host', 'Origin',
            'Referer', 'User-Agent', 'X-Authorization', 'X-Api-Id', 'X-Nektria-App', 'X-Trace', 'X-sync',
            'Cross-Origin-Embedder-Policy', 'Cross-Origin-Opener-Policy', 'X-Tenant', 'X-Api-Version', 'X-Origin',
        ];
    }

    private function readApiKey(Request $request): string
    {
        if ($request->headers->has('X-Authorization')) {
            $header = 'X-Authorization';
        } elseif ($request->headers->has('X-Api-Id')) {
            $header = 'X-Api-Id';
        } else {
            return '';
        }

        return $request->headers->get($header) ?? '';
    }
}

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

abstract class RequestListener implements EventSubscriberInterface
{
    public const string LOG_LEVEL_DEBUG = 'DEBUG';

    public const string LOG_LEVEL_INFO = 'INFO';

    public const string LOG_LEVEL_NONE = 'NONE';

    /** @var string[] */
    private readonly array $allowedCors;

    private float $executionTime;

    private ?Response $originalResponse;

    public function __construct(
        protected readonly AlertService $alertService,
        protected readonly Bus $bus,
        protected readonly ContextService $contextService,
        protected readonly LogService $logService,
        protected readonly SecurityServiceInterface $securityService,
        protected readonly SharedTemporalConsumptionCache $temporalConsumptionCache,
        protected readonly VariableCache $variableCache,
        protected readonly ProcessRegistry $processRegistry,
        ContainerInterface $container
    ) {
        /** @var string[] $cors */
        $cors = $container->getParameter('allowed_cors');
        $this->allowedCors = $cors;
        $this->executionTime = 0;
        $this->originalResponse = null;
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::RESPONSE => ['onKernelResponse', 4096],
            KernelEvents::EXCEPTION => ['onKernelException', 4096],
            KernelEvents::CONTROLLER => ['onKernelController', 4096],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $this->onRequestReceived($event->getRequest());
        $request = $event->getRequest();
        $method = $request->getMethod();
        $route = $request->attributes->get('_route') ?? '';

        if ($request->headers->has('X-sync')) {
            $this->contextService->setForceSync(true);
        }

        if (
            $method === 'GET'
            || $method === 'PUT'
            || $method === 'PATCH'
            || $method === 'POST'
        ) {
            $content = $request->getContent();

            try {
                if ($content === '') {
                    $request->request->replace();
                } elseif ($content[0] === '[') {
                    $data = JsonUtil::decode($content);
                    $request->request->replace(['*' => $data]);
                } else {
                    $data = JsonUtil::decode($content);
                    $request->request->replace($data);
                }
            } catch (Throwable) {
                try {
                    $out = [];
                    parse_str($content, $out);
                    $request->request->replace($out);
                } catch (Throwable) {
                    throw new DomainException('Bad request body.');
                }
            }
        }

        if (str_starts_with($route, 'app_common_') || str_starts_with($route, 'nektria_common_')) {
            try {
                $apiKey = $this->readApiKey($request);
            } catch (InsufficientCredentialsException) {
                $apiKey = null;
            }

            if ($apiKey !== null) {
                try {
                    $this->securityService->authenticateUser($apiKey);

                    if (!$this->validateUser($this->securityService->retrieveCurrentUser())) {
                        $this->securityService->clearAuthentication();

                        throw new InvalidAuthorizationException();
                    }
                } catch (InvalidAuthorizationException) {
                }
            }

            return;
        }

        $apiKey = $this->readApiKey($request);

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
                $this->contextService->setUserId($this->securityService->currentUser()->id);
            }
        }

        if (!$this->validateUser($this->securityService->retrieveCurrentUser())) {
            $this->securityService->clearAuthentication();

            throw new InvalidAuthorizationException();
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->bus->dispatchDelayedEvents();
        $document = new ThrowableDocument($event->getThrowable());

        $event->setResponse(new DocumentResponse(
            $document,
            $this->contextService,
            $document->status,
        ));

        $this->setHeaders($event);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();

        if (
            Request::METHOD_OPTIONS === $request->getRealMethod()
            || Request::METHOD_OPTIONS === $request->getMethod()
        ) {
            $response = new Response();
            $event->setResponse($response);
        }

        $tracer = $request->headers->get('X-Trace');
        if ($tracer !== null) {
            $this->contextService->setTraceId($tracer);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if ($response instanceof DocumentResponse && $response->document instanceof FileDocument) {
            $this->originalResponse = $response;
            $fileResponse = new BinaryFileResponse(
                $response->document->file,
            );
            $fileResponse->deleteFileAfterSend();
            $fileResponse->headers->set('Content-Type', $response->document->mime);
            if ($response->document->maxAge !== null) {
                $fileResponse->headers->set('Cache-Control', "public, max-age={$response->document->maxAge}");
                $clock = Clock::now()->add($response->document->maxAge, 'seconds');
                $fileResponse->headers->set('Expires', $clock->rfc1123String());
            }
            $fileResponse->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $response->document->filename,
            );
            $event->setResponse($fileResponse);
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $this->setHeaders($event);
        $this->executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->bus->dispatchDelayedEvents();
        $route = $event->getRequest()->attributes->get('_route') ?? '';

        if ($route === '') {
            return;
        }

        $response = $this->originalResponse ?? $event->getResponse();
        $status = $response->getStatusCode();
        $document = null;
        if ($response instanceof DocumentResponse) {
            $document = $response->document;

            if (!($document instanceof ThrowableDocument)) {
                $status = $response->getStatusCode();
            }
        }

        if ($this->contextService->isTest()) {
            return;
        }

        if (
            str_starts_with($route, 'nektria_')
            || str_starts_with($route, 'app_admin_tools_')
            || str_starts_with($route, 'app_common_')
        ) {
            $logLevel = self::LOG_LEVEL_NONE;
        } else {
            $logLevel = $this->assignLogLevel($route, $this->securityService->currentUser()?->tenant);
        }

        $responseContentRaw = ($this->originalResponse ?? $event->getResponse())->getContent();
        $length = 0;
        if (
            $event->getResponse() instanceof DocumentResponse
            && $event->getResponse()->document instanceof DocumentCollection
        ) {
            $length = $event->getResponse()->document->count();
        }

        if ($responseContentRaw === false || $responseContentRaw === '') {
            $responseContentRaw = '[]';
        }

        $requestContentRaw = $event->getRequest()->getContent();
        if ($requestContentRaw === '') {
            $requestContentRaw = '[]';
        }

        $requestContent = JsonUtil::decode($requestContentRaw);

        if ($document === null) {
            try {
                $responseContent = JsonUtil::decode($responseContentRaw);
            } catch (Throwable) {
                $responseContent = [];
            }
        } elseif ($document instanceof ThrowableDocument) {
            $responseContent = $document->toDevArray();
        } else {
            $responseContent = $document->toArray($this->contextService);
        }

        $queryBody = [];
        $queryString = $event->getRequest()->getQueryString() !== null
            ? '?' . $event->getRequest()->getQueryString()
            : '';
        parse_str($event->getRequest()->getQueryString() ?? '', $queryBody);

        $requestContent = array_merge($queryBody, $requestContent);

        $path = $event->getRequest()->getPathInfo();
        $resume = "{$path}{$queryString}";
        $rawHeadersKeys = $event->getRequest()->headers->keys();
        $headers = [];
        foreach ($rawHeadersKeys as $key) {
            $header = strtolower($key);

            if ($header === 'x-authorization' || $header === 'x-api-id') {
                $headers[$key] = '********';

                continue;
            }

            $headers[$key] = $event->getRequest()->headers->get($key);
        }

        if (isset($requestContent['email'])) {
            $requestContent['email'] = '********';
        }
        if (isset($requestContent['password'])) {
            $requestContent['password'] = '********';
        }
        if (isset($requestContent['oldPassword'])) {
            $requestContent['oldPassword'] = '********';
        }
        if (isset($requestContent['dniNie'])) {
            $requestContent['dniNie'] = '********';
        }

        if ($this->contextService->tenantId() !== null) {
            $this->temporalConsumptionCache->increase($this->contextService->tenantId(), $route);
        }

        $routeParams = $event->getRequest()->attributes->get('_route_params');
        $routeParams['path'] = $route;
        $routeParams['context'] = 'request';

        $routeParams = [
            ...$this->processRegistry->getMetadata()->data(),
            ...$routeParams,
        ];

        if ($logLevel !== self::LOG_LEVEL_NONE) {
            if ($status < 400) {
                if ($event->getRequest()->getMethod() !== Request::METHOD_GET) {
                    $isDebug = false;
                } else {
                    $isDebug = $this->contextService->context() === ContextService::INTERNAL
                        || $this->contextService->context() === ContextService::ADMIN;
                }

                if ($logLevel !== null) {
                    $isDebug = $logLevel === self::LOG_LEVEL_DEBUG;
                }

                if ($isDebug) {
                    $this->logService->debug(
                        [
                            'headers' => $headers,
                            'httpRequest' => [
                                'requestMethod' => $event->getRequest()->getMethod(),
                                'requestUrl' => $path,
                                'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                                'latency' => round($this->executionTime, 3) . 's',
                            ],
                            'request' => $requestContent,
                            'response' => $responseContent,
                            'size' => $length,
                        ],
                        $routeParams,
                        $resume,
                        in_array($route, $this->ignoreLogs(), true)
                    );
                } else {
                    $this->logService->info([
                        'headers' => $headers,
                        'httpRequest' => [
                            'requestMethod' => $event->getRequest()->getMethod(),
                            'requestUrl' => $path,
                            'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                            'latency' => round($this->executionTime, 3) . 's',
                        ],
                        'request' => $requestContent,
                        'response' => $responseContent,
                        'size' => $length,
                    ], $routeParams, $resume);
                }
            } elseif ($status < 500) {
                $this->logService->warning([
                    'headers' => $headers,
                    'httpRequest' => [
                        'requestMethod' => $event->getRequest()->getMethod(),
                        'requestUrl' => $path,
                        'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                        'latency' => round($this->executionTime, 3) . 's',
                    ],
                    'request' => $requestContent,
                    'response' => $responseContent,
                    'size' => $length,
                ], $routeParams, $resume);
            } else {
                $this->logService->temporalLogs();
                $this->logService->error([
                    'headers' => $headers,
                    'httpRequest' => [
                        'requestMethod' => $event->getRequest()->getMethod(),
                        'requestUrl' => $path,
                        'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                        'latency' => round($this->executionTime, 3) . 's',
                    ],
                    'request' => $requestContent,
                    'response' => $responseContent,
                    'size' => $length,
                ], $routeParams, $resume);
            }
        }

        if ($response instanceof DocumentResponse) {
            $document = $response->document;

            if (!($document instanceof ThrowableDocument)) {
                return;
            }

            if ($document->status >= 500) {
                $key = "{$route}_request_500";
                $key2 = "{$route}_count";
                if ($this->contextService->env() === ContextService::DEV || $this->variableCache->refreshKey($key)) {
                    $times = $this->variableCache->readInt($key2, 1);
                    $tenantName = $this->securityService->currentUser()?->tenant->name ?? 'none';
                    $method = $event->getRequest()->getMethod();
                    $path = $event->getRequest()->getPathInfo();
                    $this->alertService->sendThrowable(
                        $tenantName,
                        $method,
                        $path,
                        $requestContent,
                        $document,
                        $times,
                    );
                    $this->variableCache->saveInt($key2, 0);
                } else {
                    $times = $this->variableCache->readInt($key2);
                    $this->variableCache->saveInt($key2, $times + 1);
                }
            }
        }
    }

    protected function assignLogLevel(string $code, ?Tenant $tenant): ?string
    {
        return null;
    }

    /**
     * @return string[]
     */
    protected function ignoreLogs(): array
    {
        return [];
    }

    protected function onRequestReceived(Request $request): void
    {
    }

    protected function validateUser(User $user): bool
    {
        return true;
    }

    private function isCorsNeeded(RequestEvent | ResponseEvent $event): bool
    {
        $origin = $event->getRequest()->server->get('HTTP_ORIGIN');

        if ($origin === null) {
            return true;
        }

        return in_array('*', $this->allowedCors, true) || in_array($origin, $this->allowedCors, true);
    }

    private function readApiKey(Request $request): string
    {
        if ($request->headers->has('X-Authorization')) {
            $header = 'X-Authorization';
        } elseif ($request->headers->has('X-Api-Id')) {
            $header = 'X-Api-Id';
        } else {
            throw new InsufficientCredentialsException();
        }

        return $request->headers->get($header) ?? '';
    }

    private function setHeaders(RequestEvent | ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$this->isCorsNeeded($event)) {
            return;
        }

        if ($response !== null) {
            $response->headers->set('Access-Control-Allow-Origin', $event->getRequest()->server->get('HTTP_ORIGIN'));
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers', 'X-Authorization,Link');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', [
                'Accept', 'Accept-Encoding', 'Accept-Language', 'Access-Control-Request-Headers',
                'Access-Control-Request-Method', 'Connection', 'Content-Length', 'Content-Type', 'Host', 'Origin',
                'Referer', 'User-Agent', 'X-Authorization', 'X-Api-Id', 'X-Nektria-App', 'X-Trace', 'X-sync',
                'Cross-Origin-Embedder-Policy', 'Cross-Origin-Opener-Policy', 'X-Tenant', 'X-Api-Version', 'X-Origin',
            ]));
        }
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Listener;

use DomainException;
use Nektria\Document\DocumentCollection;
use Nektria\Document\DocumentResponse;
use Nektria\Document\FileDocument;
use Nektria\Document\ThrowableDocument;
use Nektria\Document\User;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Exception\InvalidAuthorizationException;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Service\AlertService;
use Nektria\Service\Bus;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Nektria\Service\RoleManager;
use Nektria\Service\SharedTemporalConsumptionCache;
use Nektria\Service\VariableCache;
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
    private float $executionTime;

    /** @var string[] */
    private readonly array $allowedCors;

    private ?Response $originalResponse;

    public function __construct(
        private readonly Bus $bus,
        private readonly ContextService $contextService,
        private readonly LogService $logService,
        private readonly AlertService $alertService,
        private readonly VariableCache $variableCache,
        private readonly UserServiceInterface $userService,
        private readonly SharedTemporalConsumptionCache $temporalConsumptionCache,
        private readonly string $env,
        ContainerInterface $container
    ) {
        /** @var string[] $cors */
        $cors = $container->getParameter('allowed_cors');
        $this->allowedCors = $cors;
        $this->executionTime = 0;
        $this->originalResponse = null;
    }

    /**
     * @return array<string, string|array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::RESPONSE => ['onKernelResponse', 4096],
            KernelEvents::EXCEPTION => ['onKernelException', 4096],
            KernelEvents::CONTROLLER => ['onKernelController', 4096]
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();
        $route = $request->attributes->get('_route') ?? '';

        if (
            $method === 'GET'
            || $method === 'PUT'
            || $method === 'PATCH'
            || $method === 'POST'
        ) {
            try {
                $content = $request->getContent();
                if ($content === '') {
                    $request->request->replace();
                } elseif ($content[0] === '[') {
                    $data = JsonUtil::decode($request->getContent());
                    $request->request->replace(['*' => $data]);
                } else {
                    $data = JsonUtil::decode($request->getContent());
                    $request->request->replace($data);
                }
            } catch (Throwable) {
                throw new DomainException('Bad request body.');
            }
        }

        if (str_starts_with($route, 'app_common_')) {
            try {
                $apiKey = $this->readApiKey($request);
            } catch (InsufficientCredentialsException) {
                $apiKey = null;
            }

            if ($apiKey !== null) {
                try {
                    $this->userService->authenticateUser($apiKey);

                    if (!$this->validateUser($this->userService->retrieveUser())) {
                        $this->userService->clearAuthentication();

                        throw new InvalidAuthorizationException();
                    }
                } catch (InvalidAuthorizationException) {
                }
            }

            return;
        }

        $apiKey = $this->readApiKey($request);

        if (str_starts_with($route, 'app_admin_')) {
            $this->contextService->setContext(ContextService::ADMIN);
            $this->userService->authenticateUser($apiKey);

            try {
                $this->userService->validateRole([RoleManager::ROLE_ADMIN]);
            } catch (InsufficientCredentialsException) {
                $this->userService->clearAuthentication();
            }
        } elseif (str_starts_with($route, 'app_api_')) {
            $this->contextService->setContext(ContextService::PUBLIC);
            $this->userService->authenticateUser($apiKey);
        } elseif (str_starts_with($route, 'app_api2_')) {
            $this->contextService->setContext(ContextService::PUBLIC_V2);
            $this->userService->authenticateUser($apiKey);
        } elseif (str_starts_with($route, 'app_web_')) {
            $this->contextService->setContext(ContextService::INTERNAL);
            $this->userService->authenticateUser($apiKey);
        }

        if (!$this->validateUser($this->userService->retrieveUser())) {
            $this->userService->clearAuthentication();

            throw new InvalidAuthorizationException();
        }
    }

    protected function validateUser(User $user): bool
    {
        return true;
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

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->bus->dispatchDelayedEvents();
        $document = new ThrowableDocument($event->getThrowable());

        $event->setResponse(new DocumentResponse(
            $document,
            $this->contextService,
            $document->status
        ));

        $this->setHeaders($event);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if ($response instanceof DocumentResponse && $response->document instanceof FileDocument) {
            $this->originalResponse = $response;
            $fileResponse = new BinaryFileResponse(
                $response->document->file
            );
            $fileResponse->deleteFileAfterSend();
            $fileResponse->headers->set('Content-Type', $response->document->mime);
            $fileResponse->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $response->document->filename
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

        if ($this->env === 'test') {
            return;
        }

        $ignoreRoute = $this->ignoreRoute($route);

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
            $responseContent = $document->toArray(ContextService::DEV);
        } else {
            $responseContent = $document->toArray(ContextService::INTERNAL);
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
        if (isset($requestContent['dniNie'])) {
            $requestContent['dniNie'] = '********';
        }

        if ($this->contextService->tenantId() !== null) {
            $this->temporalConsumptionCache->increase($this->contextService->tenantId(), $route);
        }

        if (!$ignoreRoute) {
            if ($status < 400) {
                $this->logService->default([
                    'headers' => $headers,
                    'context' => 'request',
                    'route_name' => $route,
                    'ref' => $this->contextService->userId() ?? 'anonymous',
                    'request' => $requestContent,
                    'size' => $length,
                    'response' => $responseContent,
                    'httpRequest' => [
                        'requestMethod' => $event->getRequest()->getMethod(),
                        'requestUrl' => $path,
                        'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                        'latency' => round($this->executionTime, 3) . 's'
                    ]
                ], $resume);
            } elseif ($status < 500) {
                $this->logService->warning([
                    'headers' => $headers,
                    'context' => 'request',
                    'route_name' => $route,
                    'ref' => $this->contextService->userId() ?? 'anonymous',
                    'request' => $requestContent,
                    'response' => $responseContent,
                    'httpRequest' => [
                        'requestMethod' => $event->getRequest()->getMethod(),
                        'requestUrl' => $path,
                        'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                        'latency' => round($this->executionTime, 3) . 's'
                    ]
                ], $resume);
            } else {
                $this->logService->error([
                    'headers' => $headers,
                    'context' => 'request',
                    'route_name' => $route,
                    'ref' => $this->contextService->userId() ?? 'anonymous',
                    'request' => $requestContent,
                    'response' => $responseContent,
                    'httpRequest' => [
                        'requestMethod' => $event->getRequest()->getMethod(),
                        'requestUrl' => $path,
                        'status' => ($this->originalResponse ?? $event->getResponse())->getStatusCode(),
                        'latency' => round($this->executionTime, 3) . 's'
                    ]
                ], $resume);
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
                    $tenantName = $this->userService->user()?->tenant->name ?? 'none';
                    $method = $event->getRequest()->getMethod();
                    $path = $event->getRequest()->getPathInfo();
                    $this->alertService->sendThrowable(
                        $tenantName,
                        $method,
                        $path,
                        $requestContent,
                        $document,
                        $times
                    );
                    $this->variableCache->saveInt($key2, 0);
                } else {
                    $times = $this->variableCache->readInt($key2, 0);
                    $this->variableCache->saveInt($key2, $times + 1);
                }
            }
        }
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
                'Referer', 'User-Agent', 'X-Authorization', 'X-API-ID', 'X-NEKTRIA-APP', 'Cross-Origin-Embedder-Policy',
                'Cross-Origin-Opener-Policy', 'X-Tenant', 'X-API-VERSION'
            ]));
        }
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

    abstract protected function ignoreRoute(string $route): bool;
}

<?php

declare(strict_types=1);

namespace Nektria\Listener;

use DomainException;
use Nektria\Document\DocumentCollection;
use Nektria\Document\DocumentResponse;
use Nektria\Document\FileDocument;
use Nektria\Document\ThrowableDocument;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Service\AlertService;
use Nektria\Service\Bus;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Nektria\Service\VariableCache;
use Nektria\Util\JsonUtil;
use Nektria\Util\Validate;
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

use function array_slice;
use function in_array;

class RequestListener implements EventSubscriberInterface
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
            } catch (Throwable $e) {
                throw new DomainException('Bad request body.', $e->getCode(), $e);
            }
        }

        $route = $request->attributes->get('_route') ?? '';

        if (str_starts_with('app_admin_private', $route)) {
            return;
        }

        if (str_contains($request->getPathInfo(), 'cache:full-empty')) {
            return;
        }

        if (str_starts_with($route, 'app_common_')) {
            return;
        }

        $header = '';
        if ($request->headers->has('X-Authorization')) {
            $header = 'X-Authorization';
        } elseif ($request->headers->has('X-API-ID')) {
            $header = 'X-API-ID';
        }

        $token = $this->readHeader($request, $header);

        if (str_starts_with($route, 'app_admin_')) {
            $tenantId = (string) ($request->attributes->get('tenantId') ?? '');

            try {
                Validate::uuid4($tenantId);
            } catch (Throwable) {
                throw new InsufficientCredentialsException();
            }

            $this->userService->authenticateAdminByApiKey($token, $tenantId);
        } else {
            $this->userService->authenticateByApiKey($token);
        }
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

        $tracer = $request->headers->get('x-trace');
        if ($tracer !== null) {
            $this->contextService->setTraceId($tracer);
        }

        $tenant = $request->headers->get('x-tenant');
        if ($tenant !== null) {
            $this->contextService->setTenantId($tenant);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->bus->dispatchDelayedEvents();
        $document = new ThrowableDocument($event->getThrowable());

        $event->setResponse(new DocumentResponse(
            $document,
            $this->contextService,
            $this->env,
            $document->status
        ));

        $this->setHeaders($event);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if ($response instanceof DocumentResponse && $response->document() instanceof FileDocument) {
            $this->originalResponse = $response;
            $fileResponse = new BinaryFileResponse(
                $response->document()->file
            );
            $fileResponse->deleteFileAfterSend();
            $fileResponse->headers->set('Content-Type', $response->document()->mime);
            $fileResponse->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $response->document()->filename
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
        $response = $this->originalResponse ?? $event->getResponse();
        $status = $response->getStatusCode();
        $document = null;
        if ($response instanceof DocumentResponse) {
            $document = $response->document();

            if (!($document instanceof ThrowableDocument)) {
                $status = $response->getStatusCode();
            }
        }

        if ($this->env === 'test') {
            return;
        }

        if (
            !str_contains($route, 'app_admin')
            && !str_contains($route, 'app_api2')
            && !str_contains($route, 'app_api')
            && !str_contains($route, 'app_web')
            && !str_contains($route, 'app_common_ping_throwexception')
        ) {
            return;
        }

        $ignoredList = [
            'app_admin_tools_command',
            'app_admin_tools_telegram',
            'app_api_area_deprecated',
            'app_api_area_getwarehouse',
            'app_common_ping_ping',
            'app_common_ping_prometheus',
            'app_common_security_login',
            'app_web_user_me',
            //  'app_admin_private_synclostorders'
        ];

        if (in_array($route, $ignoredList, true)) {
            return;
        }

        $responseContentRaw = ($this->originalResponse ?? $event->getResponse())->getContent();
        $length = 0;
        if (
            $event->getResponse() instanceof DocumentResponse
            && $event->getResponse()->document() instanceof DocumentCollection
        ) {
            $length = $event->getResponse()->document()->count();
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

                if ($route === 'app_api_grid_get' && $status < 400) {
                    $responseContent = array_slice($responseContent, 0, 120);
                }

                if ($route === 'app_api2_grid_get' && $status < 400) {
                    $responseContent = array_slice($responseContent['list'], 0, 120);
                }
            } catch (Throwable) {
                $responseContent = [];
            }
        } else {
            if ($document instanceof ThrowableDocument) {
                $responseContent = $document->toArray(ContextService::CONTEXT_PUBLIC);
            } else {
                $responseContent = $document->toArray(ContextService::CONTEXT_PUBLIC);
            }

            if ($route === 'app_api2_grid_get' && $status < 400) {
                $responseContent = array_slice($responseContent['list'], 0, 120);
            }
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
            if ($key === 'x-authorization' || $key === 'x-api-id') {
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

        if ($status < 400) {
            $this->logService->default([
                'headers' => $headers,
                'context' => 'request',
                'route_name' => $route,
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

        if ($response instanceof DocumentResponse) {
            $document = $response->document();

            if (!($document instanceof ThrowableDocument)) {
                return;
            }

            $this->logService->exception(
                $document->throwable,
                [],
                $document->status < 500
            );

            if (($document->status >= 500) && $this->variableCache->refreshKey($route)) {
                $method = $event->getRequest()->getMethod();
                $path = $event->getRequest()->getPathInfo();
                $this->alertService->sendThrowable($method, $path, $requestContent, $document);
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

    private function readHeader(Request $request, string $header): string
    {
        return $request->headers->get($header) ?? '';
    }
}

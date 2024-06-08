<?php

declare(strict_types=1);

namespace Nektria\Controller;

use Nektria\Document\ArrayDocument;
use Nektria\Document\Document;
use Nektria\Document\DocumentResponse;
use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Message\Command;
use Nektria\Message\Query;
use Nektria\Service\ContextService;
use Nektria\Util\ArrayDataFetcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class Controller
{
    protected Request $request;

    protected ArrayDataFetcher $requestData;

    public function __construct(
        protected UserServiceInterface $userService,
        protected ContextService $context,
        protected BusInterface $bus,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest() ?? new Request();
        $body = [];

        try {
            $body = $this->request->toArray();
        } catch (Throwable) {
        }
        $this->requestData = new ArrayDataFetcher(array_merge($this->request->query->all(), $body));
    }

    /**
     * @param array{
     *     currentTry: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    protected function command(
        Command $command,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): void {
        $this->bus->dispatchCommand($command, $transport, $delayMs, $retryOptions);
    }

    /**
     * @template T of Document
     * @param T $document
     */
    protected function documentResponse(Document $document, int $status = 200): DocumentResponse
    {
        return $this->response($document, $status);
    }

    /**
     * @param array{
     *     currentTry: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    protected function emptyResponse(
        ?Command $command = null,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): DocumentResponse {
        if ($command !== null) {
            $this->command($command, $transport, $delayMs, $retryOptions);
        }

        return new DocumentResponse(new ArrayDocument([]), $this->context, Response::HTTP_NO_CONTENT);
    }

    /**
     * @template T of Document
     * @param Query<T> $query
     */
    protected function queryResponse(Query $query): DocumentResponse
    {
        return $this->documentResponse($this->bus->dispatchQuery($query));
    }

    protected function response(Document $document, int $status = 200): DocumentResponse
    {
        return new DocumentResponse($document, $this->context, $status);
    }

    protected function retrieveTenant(): Tenant
    {
        return $this->userService->retrieveUser()->tenant;
    }

    protected function retrieveTenantId(): string
    {
        return $this->retrieveUser()->tenantId;
    }

    protected function retrieveUser(): User
    {
        return $this->userService->retrieveUser();
    }
}

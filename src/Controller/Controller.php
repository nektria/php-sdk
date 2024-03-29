<?php

declare(strict_types=1);

namespace Nektria\Controller;

use Nektria\Document\Document;
use Nektria\Document\DocumentResponse;
use Nektria\Document\User;
use Nektria\Infrastructure\BusInterface;
use Nektria\Message\Command;
use Nektria\Message\Query;
use Nektria\Service\ContextService;
use Nektria\Service\UserService;
use Nektria\Util\ArrayDataFetcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Controller
{
    protected Request $request;

    protected BusInterface $bus;

    protected ArrayDataFetcher $requestData;

    private ContextService $context;

    public function __construct(
        private readonly UserService $userService,
        ContextService $context,
        BusInterface $bus,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest() ?? new Request();
        $this->bus = $bus;
        $body = [];

        try {
            $body = $this->request->toArray();
        } catch (Throwable) {
        }
        $this->requestData = new ArrayDataFetcher(array_merge($this->request->query->all(), $body));
        $this->context = $context;
    }

    protected function retrieveUser(): User
    {
        return $this->userService->retrieveUser();
    }

    protected function response(Document $document, int $status = 200): DocumentResponse
    {
        return new DocumentResponse($document, $this->context, $status);
    }

    /**
     * @template T of Document
     * @param Document $document
     */
    protected function documentResponse(Document $document): JsonResponse
    {
        return $this->response($document);
    }

    /**
     * @template T of Document
     * @param Query<T> $query
     */
    protected function queryResponse(Query $query): JsonResponse
    {
        return $this->documentResponse($this->bus->dispatchQuery($query));
    }

    protected function commandResponse(Command $command): JsonResponse
    {
        $this->bus->dispatchCommand($command);

        return $this->emptyResponse();
    }

    protected function emptyResponse(): JsonResponse
    {
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    protected function retrieveTenantId(): string
    {
        return $this->retrieveUser()->tenantId;
    }
}

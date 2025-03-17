<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\UserServiceInterface;

readonly class ProxyClient
{
    public function __construct(
        protected ContextService $contextService,
        protected SharedUserV2Cache $sharedUserCache,
        private RequestClient $requestClient,
        private UserServiceInterface $userService,
    ) {
    }

    public function assignPlatforms(string $pickingShiftId): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->patch(
            "{$proxyHost}/api/admin/picking-shifts/{$pickingShiftId}/assign-platforms",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function executeTask(string $task): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->get(
            "{$proxyHost}/api/admin/tasks/{$task}",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function getBillingFile(): string
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return '';
        }

        return $this->requestClient->get(
            "{$proxyHost}/api/admin/files/billing",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        )->body;
    }

    public function importOrdersFromFile(): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->post(
            "{$proxyHost}/api/admin/orders/import",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function sendOrderIsUpdated(string $orderNumber): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->patch(
            "{$proxyHost}/api/admin/orders/{$orderNumber}/updated",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function sendOrderStatusIsDelivered(string $orderNumber): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->patch(
            "{$proxyHost}/api/admin/orders/{$orderNumber}/delivered",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function sendPickingShiftRoutes(string $pickingShiftId): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->patch(
            "{$proxyHost}/api/admin/picking-shifts/{$pickingShiftId}/send-routes",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function sendRouteIsUpdated(string $routeId): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->patch(
            "{$proxyHost}/api/admin/routes/{$routeId}/updated",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function sendSuspiciousOrderIsCreated(string $orderNumber): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->patch(
            "{$proxyHost}/api/admin/orders/{$orderNumber}/suspicious-order-created",
            headers: $this->getHeaders(),
            enableDebugFallback: true,
        );
    }

    public function uploadOrdersFileToPickingShift(string $pickingShiftId, string $filename): void
    {
        $proxyHost = $this->userService->retrieveUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient->files(
            "{$proxyHost}/api/admin/files/picking-shifts/{$pickingShiftId}/orders",
            filenames: [
                'ordersFile' => $filename
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        $tenantId = $this->contextService->tenantId() ?? 'none';
        $apiKey = $this->sharedUserCache->read("ADMIN_{$tenantId}")->apiKey ?? 'none';

        return [
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
            'X-Api-Id' => $apiKey,
            'X-Nektria-App' => 'nektria-proxy',
            'X-Trace' => $this->contextService->traceId(),
            'X-Origin' => $this->contextService->project(),
        ];
    }
}

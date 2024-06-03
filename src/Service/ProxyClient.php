<?php

declare(strict_types=1);

namespace Nektria\Service;

readonly class ProxyClient
{
    public function __construct(
        private ContextService $contextService,
        private SharedUserCache $sharedUserCache,
        private RequestClient $requestClient,
        private UserService $userService,
    ) {
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
        )->body;
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
        )->body;
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
        )->body;
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
        )->body;
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
        )->body;
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

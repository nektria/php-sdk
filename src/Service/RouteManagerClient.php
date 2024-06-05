<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;
use Throwable;

/**
 * @phpstan-type RMAddress array{
 *      addressLine1: string,
 *      addressLine2: string,
 *      city: string,
 *      countryCode: string,
 *      elevator: ?bool,
 *      latitude: float,
 *      longitude: float,
 *      postalCode: string,
 * }
 *
 * @phpstan-type RMPing array{
 *      response: string
 * }
 *
 * @phpstan-type RMRoute array{
 *     callbackUrl: ?string,
 *     estimatedDeliveryRefresh: ?int,
 *     expressOrdersNotificationEmails: string[],
 *     isochroneContourSizes: int[],
 *     pshiftRoutesNotificationEmails: string[],
 *     routeRangeCosts: array{string: float[]},
 *     routingTips:string,
 * }
 *
 * @phpstan-type RMWarehouse array{
 *     address: RMAddress,
 *     id: string,
 *     metadata: RMWarehouseMetadata,
 *     name: string,
 *     reload: bool,
 *     returnToWarehouse: bool,
 *     sendToTenantAtPickingShiftClosesAt: bool,
 *     smartCheckTransportCostEnabled: bool,
 *     timezone: string,
 *     transportCostGoal: float,
 *     transportCostGoalTolerance: int,
 *     travelMode: string,
 *     warehouseCode: ?string,
 * }
 *
 * @phpstan-type RMWarehouseMetadata array{
 *     callbackUrl: ?string,
 *     estimatedDeliveryRefresh: ?int,
 *     expressOrdersNotificationEmails: string[],
 *     isochroneContourSizes: int[],
 *     pshiftRoutesNotificationEmails: string[],
 *     routeRangeCosts: array{string: float[]},
 *     routingTips:string,
 * }
 */
readonly class RouteManagerClient
{
    public function __construct(
        private ContextService $contextService,
        private SharedUserCache $sharedUserCache,
        private RequestClient $requestClient,
        private string $routeManagerHost
    ) {
    }

    public function checkProxyPickingShiftAssignation(string $orderNumber): void
    {
        try {
            $this->requestClient->patch(
                "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}/check-proxy-assignation",
                data: [
                    'at' => Clock::now()->dateTimeString()
                ],
                headers: $this->getHeaders()
            );
        } catch (Throwable) {
        }
    }

    public function deleteOrder(string $orderNumber): void
    {
        $this->requestClient->delete(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}",
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return RMRoute[]
     */
    public function getPickingShiftRoutes(string $pickingShiftId): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$pickingShiftId}/routes",
            headers: $this->getHeaders()
        )->json();
    }

    /**
     * @return RMWarehouse
     */
    public function getWarehouse(string $id): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/warehouses/{$id}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMPing
     */
    public function ping(): array
    {
        return $this->requestClient->get("{$this->routeManagerHost}/ping")->json();
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
            'X-Nektria-App' => 'routemanager',
            'X-Trace' => $this->contextService->traceId(),
            'X-Origin' => $this->contextService->project(),
        ];
    }
}

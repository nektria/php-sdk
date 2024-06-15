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
 * @phpstan-type RMBox array{
 *      code: string,
 *      quantity: number,
 * }
 *
 * @phpstan-type RMDriver array{
 *      id: string,
 *      identificationDocument: string,
 *      latitude: float,
 *      longitude: float,
 *      positionUpdatedAt: string,
 *      warehouses: string[],
 * }
 *
 * @phpstan-type RMOrder array{
 *      address: RMAddress,
 *      area: string,
 *      createdAt: string,
 *      deliveryComment: string,
 *      finishedAt: string|null,
 *      id: string,
 *      localFinishedAt: string|null,
 *      localStatusUpdatedAt: string,
 *      localProxyAssignedAt: string|null,
 *      metadata: RMOrderMetadata,
 *      orderNumber: string,
 *      note: string,
 *      pickingShiftId: string|null,
 *      productLines: int,
 *      proxyAssignedAt: string|null,
 *      routeId: string|null,
 *      shopper: RMShopper|null,
 *      status: string,
 *      statusUpdatedAt: string,
 *      stepId: string|null,
 *      tenantId: string,
 *      timeRange: array{
 *          endTime: string,
 *          startTime: string,
 *      },
 *      type: string,
 *      updatedAt: string,
 *      warehouseId: string|null,
 *      weight: int,
 * }
 *
 * @phpstan-type RMOrderMetadata array{
 *      boxes: RMBox[],
 *      priority: int|null,
 *      products: RMProduct[],
 *      returnPickUp: bool,
 *      tags: string[],
 * }
 *
 * @phpstan-type RMPickingShift array{
 *      closesAt: string,
 *      connectivities: array{pickingShift: int, timeWindow: int, route: int}[],
 *      costPerOrder: float,
 *      createdAt: string,
 *      date: string,
 *      id: string,
 *      name: string,
 *      pickingShiftCode: string,
 *      status: string,
 *      tags: string[],
 *      timeWindows: RMTimeWindow[],
 *      totalOrders: int,
 *      totalRoutes: int,
 *      totalTrucks: string,
 *      totalUnnassignedOrders: int,
 *      updatedAt: string,
 *      warehouseId: string,
 * }
 *
 * @phpstan-type RMPing array{
 *      response: string
 * }
 *
 * @phpstan-type RMProduct array{
 *      code: string,
 *      name: string,
 *      weight: int,
 *      quantity: int,
 * }
 *
 * @phpstan-type RMRoute array{
 *     createdAt: string,
 *     distance: int,
 *     driverArrivedAtWarehouseAt: string,
 *     driverId: string|null,
 *     executionWindow: array{
 *         startTime: string,
 *         endTime: string,
 *     },
 *     id: string,
 *     name: string,
 *     pickingShiftId: string,
 *     providerId: string,
 *     stagingArea: string|null,
 *     steps: RMStep[],
 *     time: int,
 *     type: string,
 *     updatedAt: string,
 *     vehicleId: string|null,
 * }
 *
 * @phpstan-type RMShopper array{
 *     createdAt: string,
 *     delayScore: number,
 *     id: string,
 *     name: string,
 *     phone: string,
 *     shopperCode: string,
 *     tenantId: string,
 *     updatedAt: string,
 * }
 *
 * @phpstan-type RMStep array{
 *     distance: int,
 *     estimatedDelivery: string,
 *     handlingTime: int,
 *     id: string,
 *     localEstimatedDelivery: string,
 *     localEstimatedDelivery: string,
 *     localOriginalEstimatedDelivery: string,
 *     orders: RMOrder[],
 *     originalEstimatedDelivery: string,
 *     originalEstimatedDelivery: string,
 *     originalLestimatedDelivery: string,
 *     parkingTime: int,
 *     travelTime: int,
 *     type: string,
 *     waitingTime: int,
 *     warehouseId: string,
 * }
 *
 * @phpstan-type RMTimeWindow array{
 *     areas: string[],
 *     createdAt: string,
 *     endTime: string,
 *     id: string,
 *     pickingShiftId: string,
 *     priority: int,
 *     startTime: string,
 *     updatedAt: string,
 *     warehouseId: string,
 * }
 *
 * @phpstan-type RMVehicle array{
 *      code: string,
 *      createdAt: string,
 *      id: string,
 *      name: string,
 *      tenantId: string,
 *      type: string,
 *      updatedAt: string,
 *      warehouseId: string,
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
                    'at' => Clock::now()->iso8601String(),
                ],
                headers: $this->getHeaders(),
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
     * @return RMDriver
     */
    public function getDriver(string $id): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/drivers/{$id}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMOrder
     */
    public function getOrder(string $orderNumber): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMPickingShift
     */
    public function getPickingShift(string $id): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$id}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMRoute
     */
    public function getRoute(string $id): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/routes/{$id}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMVehicle
     */
    public function getVehicle(string $id): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/vehicles/{$id}",
            headers: $this->getHeaders(),
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
     * @return RMRoute[]
     */
    public function listPickingShiftRoutes(string $pickingShiftId): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$pickingShiftId}/routes",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMPickingShift[]
     */
    public function listWarehousePickingShifts(string $warehouseId, Clock $date): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/warehouses/{$warehouseId}/picking-shifts",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMWarehouse[]
     */
    public function listWarehouses(): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/warehouses",
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

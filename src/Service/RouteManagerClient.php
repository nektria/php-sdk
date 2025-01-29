<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Address;
use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;
use Nektria\Dto\Metadata;
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
 *      metadata: array<string, mixed>,
 *      name: string,
 *      positionUpdatedAt: string,
 *      warehouses: string[],
 * }
 *
 * @phpstan-type RMOrder array{
 *      address: RMAddress,
 *      area: string,
 *      boxes: array{
 *          code: string,
 *          quantity: int,
 *      }[],
 *      cost: float,
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
 *      paid: float,
 *      pickingShiftId: string|null,
 *      productLines: int,
 *      products: array{
 *          code: string,
 *          name: string,
 *          weight: int,
 *          quantity: int,
 *      }[],
 *      proxyAssignedAt: string|null,
 *      routeId: string|null,
 *      shopper: RMShopper|null,
 *      status: string,
 *      statusUpdatedAt: string,
 *      stepId: string|null,
 *      tags: string[],
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
 * @phpstan-type RMOrderMetadata array<string, mixed>
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
 *      priority: int,
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
 * @phpstan-type RMPickingShiftPlanning array{
 *      id: string,
 *      ordersWithoutRoute: RMOrder[],
 *      routes: RMRoute[],
 *      transportSmartChecks: array{
 *          type: string,
 *          message: string,
 *      }[],
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
 *     cost: float,
 *     createdAt: string,
 *     distance: int,
 *     driver: RMDriver|null,
 *     driverArrivedAtWarehouseAt: string,
 *     driverId: string|null,
 *     executionWindowEndTime: string,
 *     executionWindowStartTime: string,
 *     expectedWindowEndTime: string,
 *     expectedWindowStartTime: string,
 *     id: string,
 *     name: string,
 *     pickingShiftId: string,
 *     providerId: string,
 *     stagingArea: string|null,
 *     steps: RMStep[],
 *     time: int,
 *     totalDistance: int,
 *     totalTravelTime: int,
 *     type: string,
 *     updatedAt: string,
 *     vehicleId: string|null,
 *     warehouseId: string,
 * }
 *
 * @phpstan-type RMShopper array{
 *     createdAt: string,
 *     delayScore: number,
 *     id: string,
 *     name: string|null,
 *     phone: string|null,
 *     shopperCode: string,
 *     tenantId: string,
 *     updatedAt: string,
 * }
 *
 * @phpstan-type RMStep array{
 *     distance: int,
 *     estimatedDelivery: string,
 *     deliveryTime: int,
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
        private SharedUserV2Cache $sharedUserCache,
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

    public function deleteDriver(string $driverId): void
    {
        $this->requestClient->delete(
            "{$this->routeManagerHost}/api/admin/drivers/{$driverId}",
            headers: $this->getHeaders(),
        );
    }

    public function deleteOrder(string $orderNumber): void
    {
        $this->requestClient->delete(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}",
            headers: $this->getHeaders(),
        );
    }

    public function deletePickingShift(string $pickingShiftId): void
    {
        $this->requestClient->delete(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$pickingShiftId}",
            headers: $this->getHeaders(),
        );
    }

    public function deleteTrafficChief(
        string $trafficChiefId,
    ): void {
        $this->requestClient->delete(
            "{$this->routeManagerHost}/api/admin/traffic-chiefs/{$trafficChiefId}",
            headers: $this->getHeaders(),
        );
    }

    public function executeSaveDriverCoordinatesFromOrder(
        string $orderNumber,
        float $latitude,
        float $longitude,
        Clock $at
    ): void {
        $this->requestClient->patch(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}/save-driver-coordinates",
            data: [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'at' => $at->iso8601String(),
            ],
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
     * @return RMDriver[]
     */
    public function getDrivers(): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/drivers",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMDriver[]
     */
    public function getDriversFromWarehouse(string $warehouseId): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/warehouses/{$warehouseId}/drivers",
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
     * @return RMOrder[]
     */
    public function getOrdersFromWarehouse(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/warehouses/{$warehouseId}/orders",
            data: [
                'date' => $date->dateString(),
            ],
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
     * @return RMOrder[]
     */
    public function getPickingShiftOrders(string $pickingShiftId): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$pickingShiftId}/orders",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMPickingShiftPlanning
     */
    public function getPickingShiftPlanning(string $id): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$id}/planning",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return RMRoute[]
     */
    public function getPickingShiftRoutes(string $pickingShiftId): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$pickingShiftId}/routes",
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
     * @return RMRoute[]
     */
    public function getRoutesFromWarehouse(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient->get(
            "{$this->routeManagerHost}/api/admin/warehouses/{$warehouseId}/routes",
            data: [
                'date' => $date->dateString(),
            ],
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
     * @return RMPickingShift[]
     */
    public function getWarehousePickingShifts(string $warehouseId, Clock $date): array
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
    public function getWarehouses(): array
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
     * @param string[] $warehouses
     */
    public function saveDriver(
        string $driverId,
        ?string $name,
        ?string $identificationDocument,
        ?string $phoneNumber,
        ?array $warehouses,
        ?Metadata $metadata,
    ): void {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/drivers/{$driverId}",
            data: [
                'identificationDocument' => $identificationDocument,
                'metadata' => $metadata?->data(),
                'name' => $name,
                'phoneNumber' => $phoneNumber,
                'warehouses' => $warehouses,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveDriverCoordinates(
        string $driverId,
        float $latitude,
        float $longitude,
        Clock $at
    ): void {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/drivers/{$driverId}/coordinates",
            data: [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'at' => $at->iso8601String(),
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @param array{
     *      name: string|null,
     *      phoneNumber: string|null,
     *      shopperCode: string|null,
     * }|null $shopper
     * @param array{
     *      code: string,
     *      name: string,
     *      quantity: int,
     *      weight: int
     * }[]|null $products
     * @param array{
     *      code: string,
     *      quantity: int
     *  }[]|null $boxes
     * @param string[]|null $tags
     */
    public function saveOrder(
        string $orderNumber,
        ?string $warehouseId,
        ?string $pickingShiftId,
        ?array $shopper,
        ?Address $address,
        ?string $area,
        ?LocalClock $startTime,
        ?LocalClock $endTime,
        ?string $status,
        ?int $weight,
        ?int $productLines,
        ?float $cost,
        ?float $paid,
        ?bool $returnal,
        ?array $tags,
        ?array $products,
        ?array $boxes,
        ?string $note,
        ?Metadata $metadata,
    ): void {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}",
            data: [
                'address' => $address?->toArray(),
                'area' => $area,
                'boxes' => $boxes,
                'cost' => $cost,
                'endTime' => $endTime?->dateTimeString(),
                'orderNumber' => $orderNumber,
                'paid' => $paid,
                'pickingShiftId' => $pickingShiftId,
                'productLines' => $productLines,
                'products' => $products,
                'returnal' => $returnal,
                'shopper' => $shopper,
                'startTime' => $startTime?->dateTimeString(),
                'status' => $status,
                'tags' => $tags,
                'warehouseId' => $warehouseId,
                'weight' => $weight,
                'note' => $note,
                'metadata' => $metadata?->data(),
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @param array{
     *      code: string,
     *      quantity: int
     * }[] $boxes
     */
    public function saveOrderBoxes(string $orderNumber, array $boxes): void
    {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}/boxes",
            data: [
                'boxes' => $boxes,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveOrderStatus(string $orderNumber, string $status, Clock $at): void
    {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/orders/{$orderNumber}/status",
            data: [
                'status' => $status,
                'updatedAt' => $at->iso8601String(),
            ],
            headers: $this->getHeaders(),
        );
    }

    public function savePickingShiftStatus(string $pickingShiftId, string $status): void
    {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/picking-shifts/{$pickingShiftId}/status",
            data: [
                'status' => $status,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @param array<int, array{
     *      orders: array<int, string>,
     *  }>|null $itinerary
     */
    public function saveRoute(
        string $id,
        ?string $name,
        ?string $pickingShiftId,
        ?array $itinerary,
        ?string $platform,
        ?Metadata $metadata,
    ): void {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/routes/{$id}",
            data: [
                'name' => $name,
                'pickingShiftId' => $pickingShiftId,
                'itinerary' => $itinerary,
                'platform' => $platform,
                'metadata' => $metadata?->data(),
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveRouteDriver(string $routeId, string $driverId): void
    {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/routes/{$routeId}/driver",
            data: [
                'driverId' => $driverId,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveRouteName(string $routeId, string $name): void
    {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/routes/{$routeId}/name",
            data: [
                'name' => $name,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @param string[] $warehouses
     */
    public function saveTrafficChief(
        string $trafficChiefId,
        array $warehouses,
        string $name,
    ): void {
        $this->requestClient->put(
            "{$this->routeManagerHost}/api/admin/traffic-chiefs/{$trafficChiefId}",
            data: [
                'warehouses' => $warehouses,
                'name' => $name,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(): array
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

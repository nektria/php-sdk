<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;

/**
 * @phpstan-type YMWarehouse array{
 *     address: YMAddress,
 *     applyIncentives: bool,
 *     areas: string[],
 *     dailyInfo: array{
 *         countdownCutoff: string|null,
 *         countdownMaxOrders: int|null,
 *         countdownMaxProductLines: int|null,
 *         countdownMaxWeight: int|null,
 *         date: string,
 *         dayOff: bool|null,
 *         disableMarketingCampaign: bool|null,
 *         maxOrders: int|null,
 *         maxProductLines: int|null,
 *         maxStorePickUpOrders: int|null,
 *         maxWeight: int|null,
 *     }[],
 *     daysOff: string[],
 *     enabled: bool,
 *     extraWindowsForVip: int,
 *     id: string,
 *     itcEnabled: bool,
 *     maxOrders: int|null,
 *     maxProductLines: int|null,
 *     maxShifts: int|null,
 *     maxStorePickUpOrders: int|null,
 *     maxWeight: int|null,
 *     name: string,
 *     pricePerOrderGoal: float,
 *     timezone: string,
 *     updatedAt: string,
 *     vipSettings: array{
 *         enabled: bool,
 *         extraOrderRestrictions: int,
 *         extraShiftWeight: int,
 *         extraTimeWindowCapacity: int,
 *         extraTimeWindowLinkedCapacity: int,
 *     },
 *     warehouseCode: ?string
 * }
 *
 * @phpstan-type YMShift array{
 *     applySecondaryAreas: bool,
 *     areas: string[],
 *     id: string,
 *     color: string,
 *     showInMap: bool,
 *     createdAt: string,
 *     cutoff: string,
 *     localCutoff: string,
 *     date: string,
 *     name: string,
 *     penalty: int,
 *     status: string,
 *     tags: string[],
 *     usedWeightCapacity: int,
 *     warehouseId: string,
 *     weightCapacity: int,
 *     orders: int,
 *     nonComplementaryOrders: int
 * }
 *
 * @phpstan-type YMOrder array{
 *     address: YMAddress,
 *     area: string,
 *     createdAt: string,
 *     daysOpened: int,
 *     deliveryTime: int,
 *     id: string,
 *     incentivized: bool,
 *     locationId: string,
 *     orderNumber: string,
 *     parkingTime: int,
 *     preparationTime: int,
 *     productLines: int,
 *     reason: string,
 *     selectedSlotDelayTime: int,
 *     shiftId: string,
 *     shopperCode: string|null,
 *     timeRange: array{
 *         endTime: string,
 *         startTime: string
 *     },
 *     timeWindowId: string,
 *     transportDelayTime: int,
 *     travelTime: int,
 *     updatedAt: string,
 *     warehouseDelayTime: int,
 *     warehouseId: string,
 *     weight: int,
 *     windowsOpened: int
 * }
 *
 * @phpstan-type YMAddress array{
 *     addressLine1: string,
 *     addressLine2: string,
 *     city: string,
 *     countryCode: string,
 *     elevator: ?bool,
 *     latitude: float,
 *     longitude: float,
 *     postalCode: string,
 *     createdAt: string,
 *     updatedAt: string
 * }
 *
 * @phpstan-type YMExpressOrder array{
 *     address: YMAddress,
 *     area: string,
 *     createdAt: string,
 *     deliveryTime: int,
 *     expressPickerId: string,
 *     expressShiftId: string,
 *     id: string,
 *     lastStatusUpdate: string,
 *     locationId: string,
 *     orderNumber: string,
 *     parkingTime: int,
 *     preparationTime: int,
 *     productLines: int,
 *     shopperCode: string|null,
 *     status: string,
 *     travelTime: int,
 *     updatedAt: string,
 *     warehouseId: string,
 *     weight: int
 * }
 *
 * @phpstan-type YMPing array{
 *     response: string
 * }
 */
class YieldManagerClient
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly SharedUserCache $sharedUserCache,
        private readonly RequestClient $requestClient,
        private readonly string $yieldManagerHost
    ) {
    }

    public function deleteOrder(string $orderNumber): void
    {
        $this->requestClient->delete(
            "{$this->yieldManagerHost}/api/admin/orders/{$orderNumber}",
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return YMExpressOrder[]
     */
    public function getExpressOrdersFromWarehouseAndDate(string $warehouseId, Clock $date): array
    {
        return $this->requestClient->get(
            "{$this->yieldManagerHost}/api/admin/warehouses/{$warehouseId}/express-orders",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMOrder[]
     */
    public function getOrdersFromWarehouseAndDate(string $warehouseId, Clock $date): array
    {
        return $this->requestClient->get(
            "{$this->yieldManagerHost}/api/admin/warehouses/{$warehouseId}/orders",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMShift[]
     */
    public function getShiftsFromWarehouseAndDate(string $warehouseId, Clock $date): array
    {
        return $this->requestClient->get(
            "{$this->yieldManagerHost}/api/admin/warehouses/{$warehouseId}/shifts",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMWarehouse[]
     */
    public function getWarehouses(): array
    {
        return $this->requestClient->get(
            "{$this->yieldManagerHost}/api/admin/warehouses",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMPing
     */
    public function ping(): array
    {
        return $this->requestClient->get("{$this->yieldManagerHost}/ping")->json();
    }

    public function saveExpressOrder(
        string $orderNumber,
        ?string $shopperCode,
        int $weight,
        int $productLines,
        string $addressLine1,
        ?string $addressLine2,
        string $postalCode,
        string $city,
        string $countryCode,
        ?bool $elevator,
        float $latitude,
        float $longitude
    ): void {
        $this->requestClient->put(
            "{$this->yieldManagerHost}/api/admin/express-orders/{$orderNumber}",
            data: [
                'address' => [
                    'addressLine1' => $addressLine1,
                    'addressLine2' => $addressLine2,
                    'city' => $city,
                    'postalCode' => $postalCode,
                    'countryCode' => $countryCode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'elevator' => $elevator,
                ],
                'capacities' => [
                    'productLines' => $productLines,
                ],
                'shopperCode' => $shopperCode,
                'weight' => $weight,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveExpressOrderStatus(string $orderNumber, string $status): void
    {
        $this->requestClient->put(
            "{$this->yieldManagerHost}/api/admin/express-orders/{$orderNumber}",
            data: [
                'status' => $status,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveOrder(
        string $orderNumber,
        ?string $shopperCode,
        int $weight,
        int $productLines,
        Clock $startTime,
        Clock $endTime,
        string $addressLine1,
        ?string $addressLine2,
        string $postalCode,
        string $city,
        string $countryCode,
        ?bool $elevator,
        float $latitude,
        float $longitude,
        bool $returnal
    ): void {
        $this->requestClient->put(
            "{$this->yieldManagerHost}/api/admin/orders/{$orderNumber}",
            data: [
                'address' => [
                    'addressLine1' => $addressLine1,
                    'addressLine2' => $addressLine2,
                    'city' => $city,
                    'postalCode' => $postalCode,
                    'countryCode' => $countryCode,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'elevator' => $elevator,
                ],
                'capacities' => [
                    'productLines' => $productLines,
                ],
                'returnal' => $returnal,
                'weight' => $weight,
                'shopperCode' => $shopperCode,
                'timeRange' => [
                    'startTime' => $startTime->dateTimeString(),
                    'endTime' => $endTime->dateTimeString(),
                ],
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
            'X-Nektria-App' => 'yieldmanager',
            'X-Trace' => $this->contextService->traceId(),
            'X-Origin' => $this->contextService->project(),
        ];
    }
}

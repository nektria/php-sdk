<?php

declare(strict_types=1);

namespace Nektria\Service;

/**
 * @phpstan-type CompassPing array{
 *     response: string
 * }
 *
 * @phpstan-type CompassAddress array{
 *     addressLine1: string,
 *     postalCode: string,
 *     city: string,
 *     countryCode: string,
 *     latitude?: ?float,
 *     longitude?: ?float,
 * }
 *
 * @phpstan-type CompassCoordinate array{
 *     latitude: float,
 *     longitude: float,
 * }
 *
 * @phpstan-type CompassDistance array{
 *      distance: int,
 *      travelTime: int,
 *      originLatitude: float,
 *      originLongitude: float,
 *      destinationLatitude: float,
 *      destinationLongitude: float,
 *  }
 *
 * @phpstan-type CompassGeoPolygon array{
 *      distance: int,
 *      coordinates: CompassCoordinate[],
 *  }
 */
class CompassClient
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly SharedUserCache $sharedUserCache,
        private readonly RequestClient $requestClient,
        private readonly string $compassHost
    ) {
    }

    /**
     * @param CompassAddress $address
     * @return CompassCoordinate
     */
    public function getCoordinates(array $address): array
    {
        if (
            $address['addressLine1'] === ''
            || $address['postalCode'] === '08999'
            || $this->contextService->isTest()
        ) {
            return [
                'latitude' => 0,
                'longitude' => 0,
            ];
        }

        unset($address['latitude'], $address['longitude']);

        return $this->requestClient->get(
            "{$this->compassHost}/api/admin/addresses/coordinates",
            data: $address,
            headers: $this->getHeaders()
        )->json();
    }

    /**
     * @param CompassCoordinate[] $coordinates
     * @return CompassDistance[]
     */
    public function getDistances(string $travelMode, array $coordinates): array
    {
        $list = [];
        foreach ($coordinates as $coordinate) {
            $list[] = "{$coordinate['latitude']},{$coordinate['longitude']}";
        }

        return $this->requestClient->get(
            "{$this->compassHost}/api/admin/distances",
            data: [
                'wayPoints' => implode('|', $list),
                'travelMode' => $travelMode,
            ],
            headers: $this->getHeaders()
        )->json();
    }

    /**
     * @param CompassCoordinate $center
     * @param int[] $distances
     * @return CompassGeoPolygon[]
     */
    public function getGeoPolygons(array $center, string $travelMode, array $distances, string $type): array
    {
        return $this->requestClient->get(
            "{$this->compassHost}/api/admin/geo-polygons",
            data: [
                'center' => "{$center['latitude']},{$center['longitude']}",
                'distances' => implode(',', $distances),
                'travelMode' => $travelMode,
                'type' => $type,
            ],
            headers: $this->getHeaders()
        )->json();
    }

    /**
     * @return CompassPing
     */
    public function ping(): array
    {
        return $this->requestClient->get("{$this->compassHost}/ping")->json();
    }

    /**
     * @param CompassAddress $address
     */
    public function saveAddress(array $address): void
    {
        if (
            $address['addressLine1'] === ''
            || $address['postalCode'] === '08999'
            || $this->contextService->isTest()
        ) {
            return;
        }

        $this->requestClient->put(
            "{$this->compassHost}/api/admin/addresses",
            data: $address,
            headers: $this->getHeaders()
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
            'X-Nektria-App' => 'compass',
            'X-Trace' => $this->contextService->traceId(),
            'X-Origin' => $this->contextService->project(),
        ];
    }
}

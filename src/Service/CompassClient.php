<?php

declare(strict_types=1);

namespace Nektria\Service;

/**
 * @phpstan-type CompassPing array{
 *      response: string
 *  }
 *
 * @phpstan-type CompassAddress array{
 *      addressLine1: string,
 *      postalCode: string,
 *      city: string,
 *      countryCode: string,
 *      latitude?: ?float,
 *      longitude?: ?float,
 *  }
 *
 * @phpstan-type CompassCoordinates array{
 *      latitude: float,
 *      longitude: float,
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
        $this->requestClient->put(
            "{$this->compassHost}/api/admin/address",
            data: $address,
            headers: $this->getHeaders()
        );
    }

    /**
     * @param CompassAddress $address
     * @return CompassCoordinates
     */
    public function getCoordinates(array $address): array
    {
        if ($address['addressLine1'] === '') {
            return [
                'latitude' => 0,
                'longitude' => 0,
            ];
        }

        if ($address['postalCode'] === '08999') {
            return [
                'latitude' => 0,
                'longitude' => 0,
            ];
        }

        if ($this->contextService->isTest()) {
            return [
                'latitude' => 0,
                'longitude' => 0,
            ];
        }

        unset($address['latitude'], $address['longitude']);

        return $this->requestClient->get(
            "{$this->compassHost}/api/admin/address/coordinates",
            data: $address,
            headers: $this->getHeaders()
        )->json();
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
            'X-Tenant' => $tenantId,
            'X-Trace' => $this->contextService->traceId(),
        ];
    }
}

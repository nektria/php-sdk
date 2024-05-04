<?php

declare(strict_types=1);

namespace Nektria\Service;

/**
 * @phpstan-type CompassPing array{
 *      response: string
 *  }
 *
 * @phpstan-type CompassAddress array{
 *      name: string,
 *      surname: string,
 *      street: string,
 *      city: string,
 *      country: string,
 *      latitude?: ?float,
 *      longitude?: ?float,
 *  }
 *
 * @phpstan-type CompassCoordinates array{
 *      name: string,
 *      surname: string,
 *      street: string,
 *      city: string,
 *      country: string,
 *      latitude?: ?float,
 *      longitude?: ?float,
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
            "{$this->compassHost}/admin/address",
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
        unset($address['latitude'], $address['longitude']);

        return $this->requestClient->get(
            "{$this->compassHost}/admin/address/coordinates",
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

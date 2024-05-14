<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;

/**
 * @phpstan-type MetricsDeliveryInfo array{
 *      orderNumber: string,
 *      at: Clock
 * }
 *
 * @phpstan-type MetricsPing array{
 *       response: string
 *  }
 */
class MetricsClient
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly SharedUserCache $sharedUserCache,
        private readonly RequestClient $requestClient,
        private readonly string $metricsHost
    ) {
    }

    /**
     * @return MetricsPing
     */
    public function ping(): array
    {
        return $this->requestClient->get("{$this->metricsHost}/ping")->json();
    }

    /**
     * @param Clock $at should be local time
     */
    public function deliverOrder(string $order, Clock $at): void
    {
        $this->requestClient->patch("{$this->metricsHost}/api/admin/orders/deliver", [
            'json' => [
                'orderNumber' => $order,
                'at' => $at->dateTimeString(),
            ],
            'headers' => $this->getHeaders(),
        ]);
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
            'X-Nektria-App' => 'metrics',
            'X-Trace' => $this->contextService->traceId(),
            'X-Origin' => $this->contextService->project(),
        ];
    }
}

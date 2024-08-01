<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;

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
        private readonly ContextService    $contextService,
        private readonly SharedUserV2Cache $sharedUserCache,
        private readonly RequestClient     $requestClient,
        private readonly string            $metricsHost
    ) {
    }

    public function deliverOrder(string $orderNumber, LocalClock $at): void
    {
        $this->requestClient->patch(
            "{$this->metricsHost}/api/admin/orders/{$orderNumber}/deliver",
            data: [
                'at' => $at->dateTimeString(),
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return MetricsPing
     */
    public function ping(): array
    {
        return $this->requestClient->get("{$this->metricsHost}/ping")->json();
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

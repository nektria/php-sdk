<?php

declare(strict_types=1);

namespace Nektria\Service;

use App\Shared\Exceptions\MercureClientException;
use Nektria\Document\Document;
use Nektria\Exception\NektriaException;
use Nektria\Util\JsonUtil;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Throwable;

use function explode;

readonly class SocketService
{
    public function __construct(
        private HubInterface $hub,
        private ContextService $contextService,
        private ?string $mercureHost,
        private ?string $mercureToken,
    ) {
    }

    /**
     * @param mixed[] $event
     */
    public function publish(string $type, Document $data): void
    {
        if ($this->mercureToken === 'none' || $this->mercureHost === 'none') {
            return;
        }

        $tmpContext = clone $this->contextService;
        $tmpContext->setContext(ContextService::INTERNAL);

        try {
            $this->hub->publish(new Update("/v1/{$this->contextService->tenantId()}", JsonUtil::encode([
                'type' => $type,
                'payload' => $data->toArray($tmpContext),
            ]), true));
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }
}

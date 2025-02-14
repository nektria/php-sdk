<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Document;
use Nektria\Exception\NektriaException;
use Nektria\Util\JsonUtil;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Throwable;

readonly class SocketService
{
    public function __construct(
        private HubInterface $hub,
        private ContextService $contextService,
        private ?string $mercureHost,
        private ?string $mercureToken,
    ) {
    }

    public function publishToTenant(string $type, Document $data): void
    {
        if ($this->mercureToken === 'none' || $this->mercureHost === 'none') {
            return;
        }

        $tmpContext = clone $this->contextService;
        $tmpContext->setContext(ContextService::INTERNAL);

        try {
            $this->hub->publish(new Update("/{$this->contextService->tenantId()}", JsonUtil::encode([
                'type' => $type,
                'payload' => $data->toArray($tmpContext),
            ]), true));
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function publishToUser(string $type, Document $data): void
    {
        if ($this->mercureToken === 'none' || $this->mercureHost === 'none') {
            return;
        }

        if ($this->contextService->userId() === null) {
            return;
        }

        $tmpContext = clone $this->contextService;
        $tmpContext->setContext(ContextService::INTERNAL);

        try {
            $userId = $this->contextService->userId();
            $this->hub->publish(new Update("/{$userId}", JsonUtil::encode([
                'type' => $type,
                'payload' => $data->toArray($tmpContext),
            ]), true));
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }
}

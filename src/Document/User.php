<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

class User implements Document
{
    private ?string $socketsToken;

    /**
     * @var string[]|null
     */
    private ?array $topics;

    /**
     * @param string[] $warehouses
     */
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly array $warehouses,
        public readonly string $apiKey,
        public readonly string $role,
        public readonly string $tenantId,
        public readonly Tenant $tenant,
        public readonly ?string $dniNie
    ) {
        $this->socketsToken = null;
        $this->topics = null;
    }

    /**
     * @param string[] $allowedTopics
     */
    public function appendSockets(string $token, array $allowedTopics): void
    {
        $this->topics = $allowedTopics;
        $this->socketsToken = $token;
    }

    public function toArray(ContextService $context): mixed
    {
        if ($context->context() === ContextService::INTERNAL) {
            return [
                'id' => $this->id,
                'email' => $this->email,
                'warehouses' => $this->warehouses,
                'role' => $this->role,
                'dniNie' => $this->dniNie,
                'language' => 'en',
                'tenant' => $this->tenant->toArray($context),
                'socketsToken' => $this->socketsToken,
                'allowedTopics' => $this->topics,
            ];
        }

        if ($context->context() === ContextService::ADMIN) {
            return [
                'id' => $this->id,
                'email' => $this->email,
                'warehouses' => $this->warehouses,
                'role' => $this->role,
                'dniNie' => $this->dniNie,
                'language' => 'en',
                'tenant' => $this->tenant->toArray($context),
                'socketsToken' => $this->socketsToken,
                'allowedTopics' => $this->topics,
                'apiKey' => $this->apiKey,
            ];
        }

        return [
            'id' => $this->id,
            'email' => $this->email,
            'warehouses' => $this->warehouses,
            'role' => $this->role,
            'dniNie' => $this->dniNie,
            'tenant' => $this->tenant->toArray($context),
        ];
    }
}

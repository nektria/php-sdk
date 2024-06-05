<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\SocketInfo;
use Nektria\Service\ContextService;

readonly class User extends Document
{
    private SocketInfo $socketInfo;

    /**
     * @param string[] $warehouses
     */
    public function __construct(
        public string $id,
        public string $email,
        public array $warehouses,
        public string $apiKey,
        public string $role,
        public string $tenantId,
        public Tenant $tenant,
        public ?string $dniNie
    ) {
        $this->socketInfo = new SocketInfo();
    }

    /**
     * @param string[] $allowedTopics
     */
    public function appendSockets(string $token, array $allowedTopics): void
    {
        $this->socketInfo->appendSockets($token, $allowedTopics);
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
                'socketsToken' => $this->socketInfo->socketsToken(),
                'allowedTopics' => $this->socketInfo->topics(),
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
                'socketsToken' => $this->socketInfo->socketsToken(),
                'allowedTopics' => $this->socketInfo->topics(),
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

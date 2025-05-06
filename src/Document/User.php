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
        public string $name,
        public array $warehouses,
        public string $apiKey,
        public string $role,
        public string $tenantId,
        public Tenant $tenant,
        public ?string $dniNie,
        public ?string $aiThreadId,
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

    public function toArray(ContextService $context): array
    {
        if ($context->context() === ContextService::INTERNAL) {
            return [
                'allowedTopics' => $this->socketInfo->topics(),
                'dniNie' => $this->dniNie,
                'email' => $this->email,
                'name' => $this->name,
                'id' => $this->id,
                'language' => 'en',
                'role' => $this->role,
                'socketsToken' => $this->socketInfo->socketsToken(),
                'tenant' => $this->tenant->toArray($context),
                'warehouses' => $this->warehouses,
            ];
        }

        if ($context->context() === ContextService::ADMIN) {
            return [
                'aiThreadId' => $this->aiThreadId,
                'allowedTopics' => $this->socketInfo->topics(),
                'apiKey' => $this->apiKey,
                'dniNie' => $this->dniNie,
                'email' => $this->email,
                'id' => $this->id,
                'language' => 'en',
                'name' => $this->name,
                'role' => $this->role,
                'socketsToken' => $this->socketInfo->socketsToken(),
                'tenant' => $this->tenant->toArray($context),
                'warehouses' => $this->warehouses,
            ];
        }

        return [
            'email' => $this->email,
            'id' => $this->id,
            'role' => $this->role,
            'warehouses' => $this->warehouses,
        ];
    }
}

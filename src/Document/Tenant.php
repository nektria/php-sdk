<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\TenantMetadata;
use Nektria\Service\ContextService;

readonly class Tenant extends Document
{
    public function __construct(
        public string $id,
        public string $name,
        public TenantMetadata $metadata,
        public ?string $aiAssistantId,
        public string $timezone,
    ) {
    }

    public function toArray(ContextService $context): array
    {
        if ($context->context() === ContextService::ADMIN) {
            return [
                'aiAssistantId' => $this->aiAssistantId,
                'id' => $this->id,
                'name' => $this->name,
                'metadata' => $this->metadata->toArray($context),
                'timezone' => $this->timezone,
            ];
        }

        if ($context->context() === ContextService::INTERNAL) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'metadata' => $this->metadata->toArray($context),
                'timezone' => $this->timezone,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'availableTags' => $this->metadata->availableTags(),
            'timezone' => $this->timezone,
        ];
    }
}

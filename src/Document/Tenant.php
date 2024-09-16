<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\TenantMetadata;
use Nektria\Service\ContextService;

use function in_array;

readonly class Tenant extends Document
{
    public function __construct(
        public string $id,
        public string $name,
        public TenantMetadata $metadata
    ) {
    }

    public function toArray(ContextService $context): array
    {
        if (in_array($context->context(), [ContextService::ADMIN, ContextService::INTERNAL], true)) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'metadata' => $this->metadata->toArray(),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'availableTags' => $this->metadata->availableTags(),
        ];
    }
}

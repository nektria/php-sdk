<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\TenantMetadata;
use Nektria\Service\ContextService;

use function in_array;

class Tenant implements Document
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly TenantMetadata $metadata
    ) {
    }

    public function toArray(string $model): mixed
    {
        if (in_array($model, [ContextService::ADMIN, ContextService::INTERNAL], true)) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'metadata' => $this->metadata->toArray()
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'availableTags' => $this->metadata->availableTags()
        ];
    }
}

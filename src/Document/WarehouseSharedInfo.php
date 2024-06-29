<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\Address;
use Nektria\Service\ContextService;

readonly class WarehouseSharedInfo extends Document
{
    /**
     * @param string[] $areas
     */
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $name,
        public string $timezone,
        public string $warehouseCode,
        public array $areas,
        public bool $enabled,
        public Address $address,
    ) {
    }

    public function toArray(ContextService $context): mixed
    {
        return [
            'address' => $this->address->toArray(),
            'areas' => $this->areas,
            'enabled' => $this->enabled,
            'id' => $this->id,
            'name' => $this->name,
            'tenantId' => $this->tenantId,
            'timezone' => $this->timezone,
            'warehouseCode' => $this->warehouseCode,
        ];
    }
}

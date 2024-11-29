<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Document\Document;
use Nektria\Service\ContextService;

readonly class Metadata extends Document
{
    /** @var mixed[] */
    private array $data;

    /**
     * @param mixed[] $data
     */
    final public function __construct(
        array $data = []
    ) {
        $this->data = $data;
    }

    /**
     * @return mixed[]
     */
    public function data(): array
    {
        return $this->data;
    }

    public function getField(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * @param mixed[] $data
     */
    public function mergeData(array $data): static
    {
        $newMetadata = [...$this->data];
        foreach ($data as $key => $value) {
            $newMetadata[$key] = $value;
        }

        return new static($newMetadata);
    }

    public function toArray(ContextService $context): array
    {
        return $this->data;
    }

    public function updateField(string $field, mixed $value): static
    {
        if ($this->getField($field) === $value) {
            return $this;
        }

        $data = [...$this->data];
        $data[$field] = $value;

        return new static($data);
    }
}

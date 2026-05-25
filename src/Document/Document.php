<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Exception\NektriaException;
use Nektria\Service\ContextService;

readonly abstract class Document
{
    public function __toString(): string
    {
        if (property_exists($this, 'id')) {
            return (string) $this->id;
        }

        throw new NektriaException('E_500', "Document {$this::class} does not have an id attribute");
    }

    /**
     * @return mixed[]
     */
    abstract public function toArray(?ContextService $context): array;
}

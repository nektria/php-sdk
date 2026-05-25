<?php

declare(strict_types=1);

namespace Nektria\Document;

use LogicException;
use Nektria\Service\ContextService;

use function sprintf;

readonly abstract class Document
{
    public function __toString(): string
    {
        if (property_exists($this, 'id')) {
            return (string) $this->id;
        }

        throw new LogicException(sprintf('Document %s does not have an id attribute', $this::class));
    }

    /**
     * @return mixed[]
     */
    abstract public function toArray(?ContextService $context): array;
}

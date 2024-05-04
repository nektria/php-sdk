<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

class DatabaseValue implements Document
{
    public function __construct(public readonly mixed $data)
    {
    }

    public function toArray(ContextService $context): mixed
    {
        return $this->data;
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Document;

class DatabaseValue implements Document
{
    public function __construct(public readonly mixed $data)
    {
    }

    public function toArray(string $model): mixed
    {
        return $this->data;
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Document;

interface Document
{
    public function toArray(string $model): mixed;
}

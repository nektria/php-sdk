<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

class MissingFieldRequiredToCreateClassException extends RuntimeException
{
    public function __construct(string $resource, string $field)
    {
        parent::__construct("Field '{$field}' is mandatory when creating a '{$resource}'.");
    }
}

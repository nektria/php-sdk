<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

class InvalidRequestParamException extends RuntimeException
{
    public function __construct(string $field, string $mustBeType)
    {
        parent::__construct("Invalid field '{$field}', {$mustBeType} is required.");
    }
}

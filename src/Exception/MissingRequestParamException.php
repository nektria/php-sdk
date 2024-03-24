<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

class MissingRequestParamException extends RuntimeException
{
    public function __construct(string $field)
    {
        parent::__construct("Missing field '{$field}'.");
    }
}

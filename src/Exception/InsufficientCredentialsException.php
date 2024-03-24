<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

class InsufficientCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Insufficient credentials.');
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

class InvalidAuthorizationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}

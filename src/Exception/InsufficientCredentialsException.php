<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InsufficientCredentialsException extends NektriaRuntimeException
{
    public function __construct()
    {
        parent::__construct('Insufficient credentials.');
    }
}

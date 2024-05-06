<?php

declare(strict_types=1);

namespace Nektria\Exception;

class InvalidAuthorizationException extends NektriaRuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Exception;

class MissingRequestFileException extends NektriaRuntimeException
{
    public function __construct(string $field)
    {
        parent::__construct("Missing file '{$field}'.");
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

abstract class NektriaRuntimeException extends RuntimeException
{
    private bool $silent = false;

    public function makeSilent(): void
    {
        $this->silent = true;
    }

    public function silent(): bool
    {
        return $this->silent;
    }
}

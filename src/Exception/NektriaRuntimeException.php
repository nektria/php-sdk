<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;
use Throwable;

abstract class NektriaRuntimeException extends RuntimeException
{
    private bool $silent;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, bool $silent = false)
    {
        parent::__construct($message, $code, $previous);
        $this->silent = $silent;
    }

    public function makeSilent(): void
    {
        $this->silent = true;
    }

    public function silent(): bool
    {
        return $this->silent;
    }
}

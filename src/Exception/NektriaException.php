<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;
use Throwable;

class NektriaException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function new(Throwable $e): self
    {
        if ($e instanceof self) {
            return $e;
        }

        return new self($e->getMessage(), $e->getCode(), $e);
    }

    public function realException(): Throwable
    {
        $current = $this;
        while ($current instanceof self && $current->getPrevious() !== null) {
            $current = $current->getPrevious();
        }

        return $current;
    }
}

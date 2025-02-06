<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Throwable;

class NektriaException extends NektriaRuntimeException
{
    public static function getReal(Throwable $e)
    {
        if ($e instanceof self) {
            return $e->getReal($e->getPrevious());
        }

        return $e;
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

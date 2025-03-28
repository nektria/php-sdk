<?php

declare(strict_types=1);

namespace Nektria\Message;

interface Command
{
    /**
     * @return array<string, string>
     */
    public function params(): array;

    public function ref(): string;
}

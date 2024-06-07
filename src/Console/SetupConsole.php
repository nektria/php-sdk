<?php

declare(strict_types=1);

namespace Nektria\Console;

class SetupConsole extends Console
{
    public function __construct()
    {
        parent::__construct('sdk:setup');
    }

    protected function play(): void
    {
        $this->output()->writeln('done');
    }
}

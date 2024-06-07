<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;

class SetupConsole extends Console
{
    public function __construct()
    {
        parent::__construct('debug:setup');
    }

    protected function play(): void
    {
        $this->output()->writeln('done');
    }
}

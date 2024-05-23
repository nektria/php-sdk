<?php

declare(strict_types=1);

namespace Nektria\Console;

use RuntimeException;

class PingConsole extends Console
{
    public function __construct()
    {
        parent::__construct('sdk:ping');
    }

    protected function play(): void
    {
        if (file_exists('/tmp/entity_manager_is_closed')) {
            unlink('/tmp/entity_manager_is_closed');

            throw new RuntimeException('Entity manager is closed');
        }

        $this->output()->writeln('pong');
    }
}

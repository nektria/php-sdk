<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Util\JsonUtil;
use Symfony\Component\Process\Process;

class StaticAnalysisConsole extends Console
{
    public function __construct()
    {
        parent::__construct('sdk:static-analysis');
    }

    protected function play(): void
    {
        $command = new Process(array_merge(['../bin/console', 'debug:router', '--format=json']));
        $command->run();

        $data = JsonUtil::decode($command->getOutput());
    }
}

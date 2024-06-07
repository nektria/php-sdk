<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Exception\NektriaException;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Symfony\Component\Process\Process;

use function count;

class IncreaseVersionConsole extends Console
{
    public function __construct()
    {
        parent::__construct('sdk:increase-version');
    }

    protected function play(): void
    {
        $composer = JsonUtil::decode(FileUtil::read('composer.json'));
        $this->output()->write($composer['version']);
    }
}

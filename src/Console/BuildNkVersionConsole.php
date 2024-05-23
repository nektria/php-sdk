<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Dto\Clock;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;

class BuildNkVersionConsole extends Console
{
    public function __construct()
    {
        parent::__construct('sdk:version');
    }

    protected function configure(): void
    {
        $this->addArgument('branch');
    }

    protected function play(): void
    {
        $commit = substr(StringUtil::trim((string) exec('git rev-parse HEAD')), 0, 7);
        $total = exec('git rev-list --count HEAD');
        $branch = $this->input()->getArgument('branch') ?? exec('git rev-parse --abbrev-ref HEAD');
        $version = $branch === 'master' ? "v{$total}" : "v{$total}-{$branch}";

        FileUtil::write('NK_VERSION', JsonUtil::encode([
            'createdAt' => Clock::now()->iso8601String('Europe/Madrid'),
            'hash' => $commit,
            'type' => 'Release',
            'version' => $version,
        ]));
    }
}

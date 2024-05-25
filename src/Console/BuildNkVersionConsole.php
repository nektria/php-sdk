<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Dto\Clock;
use Nektria\Service\ContextService;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;

class BuildNkVersionConsole extends Console
{
    public function __construct(
        private readonly ContextService $contextService,
    ) {
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

        $this->output()->writeln(JsonUtil::encode([
            'builtAt' => Clock::now()->iso8601String('Europe/Madrid'),
            'hash' => $commit,
            'project' => $this->contextService->project(),
            'type' => 'Release',
            'version' => $version,
        ], true));
    }
}

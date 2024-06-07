<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Service\ContextService;
use Nektria\Util\FileUtil;

class SetupConsole extends Console
{
    public function __construct(
        private readonly ContextService $contextService
    ) {
        parent::__construct('debug:setup');
    }

    protected function play(): void
    {
        $this->copyDir('vendor/nektria/php-sdk/src/assets/bin', 'bin');
        $this->copyDir('vendor/nektria/php-sdk/src/assets/server', 'server');
        $this->output()->writeln('done');
    }

    private function copyDir(string $from, string $to): void
    {
        $files = scandir($from);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fromPath = "{$from}/{$file}";
            $toPath = "{$to}/{$this->fix($file)}";

            if (is_dir($fromPath)) {
                $this->copyDir($fromPath, $toPath);
            } else {
                $this->copyFile($fromPath, $toPath);
            }
        }
    }

    private function copyFile(string $from, string $to): void
    {
        FileUtil::write($to, $this->fix(FileUtil::read($from)));
    }

    private function fix(string $text): string
    {
        return str_replace('__PROJECT__', $this->contextService->project(), $text);
    }
}

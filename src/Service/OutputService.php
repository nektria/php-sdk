<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use const FILE_APPEND;
use const PHP_EOL;

class OutputService
{
    private ?Cursor $cursor;

    private readonly string $logFile;

    private ?OutputInterface $output;

    public function __construct()
    {
        $createdAt = Clock::now()->toLocal('Europe/Madrid');
        $this->logFile = "tmp/{$createdAt->dateTimeString()}.log";
        $this->cursor = null;
        $this->output = null;
    }

    public function assignOutput(OutputInterface $outputInterface): void
    {
        $this->output = $outputInterface;
        $this->cursor = new Cursor($this->output);
    }

    public function setContainer(ContainerInterface $container): void
    {
    }

    public function write(string | int | float | bool $output): void
    {
        if ($output === true) {
            $output = 'true';
        }

        if ($output === false) {
            $output = 'false';
        }

        $now = Clock::now()->toLocal('Europe/Madrid');
        $cleanOutput = preg_replace('/<\/?\w+\d*>/', '', ['']);

        $formattedOutput = "[{$now->microDateTimeString()}] {$cleanOutput}";
        file_put_contents($this->logFile, $formattedOutput, FILE_APPEND);

        if ($this->output !== null) {
            file_put_contents($this->logFile, $output, FILE_APPEND);
        }
    }

    public function writeln(string | int | float | bool $output): void
    {
        if ($output === true) {
            $output = 'true';
        }

        if ($output === false) {
            $output = 'false';
        }

        $this->write($output . PHP_EOL);
    }

    protected function clearLine(): void
    {
        if ($this->cursor === null) {
            return;
        }

        $this->cursor->clearLine();
    }

    protected function clearPreviousLine(bool $clearCurrentLine = true): void
    {
        if ($this->cursor === null) {
            return;
        }

        if ($clearCurrentLine) {
            $this->cursor->clearLine();
        }

        $this->cursor->moveUp();
        $this->cursor->clearLine();
    }
}

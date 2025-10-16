<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;
use RuntimeException;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\OutputInterface;

class OutputService extends AbstractService
{
    private Cursor|null $cursor;
    private readonly string $logFile;
    private OutputInterface|null $output;

    public function __construct()
    {
        parent::__construct();
        $createdAt = Clock::now()->toLocal('Europe/Madrid');
        $this->logFile = "tmp/{$createdAt->dateTimeString()}.log";
        $this->cursor = null;
    }

    public function assignOutput(OutputInterface $outputInterface): void
    {
        $this->output = $outputInterface;
        $this->cursor = new Cursor($this->output);
    }

    public function write(string|int|float|bool $output): void
    {
        if ($output === true) {
            $output = 'true';
        }

        if ($output === false) {
            $output = 'false';
        }

        $now = Clock::now()->toLocal('Europe/Madrid');
        $cleanOutput = preg_replace('/<\/?\w+\d*>/', '', $output);
        $formattedOutput = "[{$now->microDateTimeString()}] {$cleanOutput}";
        file_put_contents($this->logFile, $formattedOutput, FILE_APPEND);

        if ($this->output !== null) {
            file_put_contents($this->logFile, $output, FILE_APPEND);
        }
    }

    public function writeln(string|int|float|bool $output): void
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
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

    public function clearLine(): void
    {
        if ($this->cursor === null) {
            return;
        }

        $this->cursor->clearLine();
    }

    public function clearPreviousLine(bool $clearCurrentLine = true): void
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

    public function error(string | int | float | bool $output): void
    {
        $this->writeln("<red1>{$this->fixMessage($output)}</red1>");
    }

    public function info(string | int | float | bool $output): void
    {
        $this->writeln("<white1>{$this->fixMessage($output)}</white1>");
    }

    public function log(string | int | float | bool $output): void
    {
        $this->writeln("<white>{$this->fixMessage($output)}</white>");
    }

    public function setContainer(ContainerInterface $container): void
    {
    }

    public function warning(string | int | float | bool $output): void
    {
        $this->writeln("<yellow>{$this->fixMessage($output)}</yellow>");
    }

    public function write(string | int | float | bool $output): void
    {
        $output = $this->fixMessage($output);

        $now = Clock::now()->toLocal('Europe/Madrid');
        $cleanOutput = preg_replace('/<\/?\w+\d*>/', '', $output);

        $formattedOutput = "[{$now->microDateTimeString()}] {$cleanOutput}";
        file_put_contents($this->logFile, $formattedOutput, FILE_APPEND);

        $this->output?->write($output);
    }

    public function writeln(string | int | float | bool $output): void
    {
        $this->write($this->fixMessage($output) . PHP_EOL);
    }

    private function fixMessage(string | int | float | bool $output): string
    {
        if ($output === true) {
            $output = 'true';
        }

        if ($output === false) {
            $output = 'false';
        }

        return (string) $output;
    }
}

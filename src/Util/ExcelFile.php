<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;
use Nektria\Exception\NektriaException;
use XLSXWriter;

class ExcelFile
{
    private string $worksheet;

    private function __construct(
        private readonly string $file,
        private readonly XLSXWriter $spreadsheet,
    ) {
        $this->worksheet = 'Sheet1';
    }

    public static function new(string $file): self
    {
        return new self($file, new XLSXWriter());
    }

    public function save(): void
    {
        $this->spreadsheet->writeToFile($this->file);
    }

    public function setCell(int $row, int $column, string $value): void
    {
        throw new NektriaException('Not implemented');
    }

    /**
     * @param string[] $values
     */
    public function setCol(int $row, int $column, array $values): void
    {
        throw new NektriaException('Not implemented');
    }

    /**
     * @param string[] $values
     */
    public function setRow(int $row, int $column, array $values): void
    {
        $this->spreadsheet->writeSheetRow($this->worksheet, $values);
    }

    public function transformToDate(Clock | LocalClock $clock): string
    {
        return "{$clock->day()}/{$clock->month()}/{$clock->year()}";
    }
}

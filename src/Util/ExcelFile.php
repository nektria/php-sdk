<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelFile
{
    private Worksheet $worksheet;

    private function __construct(
        private readonly string $file,
        private readonly Spreadsheet $spreadsheet,
    ) {
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    public static function new(string $file): self
    {
        return new self($file, new Spreadsheet());
    }

    public function save(): void
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->file);
    }

    public function setCell(int $row, int $column, string $value): void
    {
        $this->worksheet->setCellValue([$column, $row], $value);
    }

    /**
     * @param string[] $values
     */
    public function setCol(int $row, int $column, array $values): void
    {
        foreach ($values as $i => $iValue) {
            $this->setCell($row + $i, $column, $iValue);
        }
    }

    /**
     * @param string[][] $values
     */
    public function setColRow(int $row, int $column, array $values): void
    {
        foreach ($values as $i => $iValue) {
            $this->setCol($row, $column + $i, $iValue);
        }
    }

    /**
     * @param string[] $values
     */
    public function setRow(int $row, int $column, array $values): void
    {
        foreach ($values as $i => $iValue) {
            $this->setCell($row, $column + $i, $iValue);
        }
    }

    public function transformToDate(Clock|LocalClock $clock): string
    {
        return "{$clock->day()}/{$clock->month()}/{$clock->year()}";
    }
}

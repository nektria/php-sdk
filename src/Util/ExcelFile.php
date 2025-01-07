<?php

declare(strict_types=1);

namespace Nektria\Util;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

readonly class ExcelFile
{
    private Spreadsheet $spreadsheet;

    private function __construct(
        public string $file,
    ) {
        $this->spreadsheet = IOFactory::load($this->file);
    }

    public static function load(string $file): self
    {
        return new self($file);
    }
    public static function new(string $file): self
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($file);

        return new self($file);
    }

    public function save(): void
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->file);
    }

    public function setCell(string $cell, string $value): void
    {
        $this->spreadsheet->getActiveSheet()->setCellValue($cell, $value);
    }

    public function getCell(string $cell): string
    {
        return $this->spreadsheet->getActiveSheet()->getCell($cell)->getValue();
    }

    public function getCell2(string $column, string $row): string
    {
        return $this->spreadsheet->getActiveSheet()->getCell($column . $row)->getValue();
    }

    public function setCell2(string $column, string $row, string $value): void
    {
        $this->spreadsheet->getActiveSheet()->setCellValue($column . $row, $value);
    }
}

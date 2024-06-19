<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Util\ExcelFile;

class TestExcelConsole extends Console
{
    public function __construct()
    {
        parent::__construct('debug:test:excel');
    }

    protected function play(): void
    {
        $excel = ExcelFile::new('./tmp/test.xlsx');
        $excel->setCell(1, 1, 'Hello, World!');
        $excel->setCell(2, 2, '?');

        $excel->setCol(3, 1, ['0', '1', '2']);
        $excel->setRow(3, 3, ['a', 'b', 'c']);

        $excel->setCell(6, 1, '=SUM(A3:A5)');

        $excel->setColRow(5, 3, [
            ['A', 'B', 'C'],
            ['D', 'E', 'F'],
            ['G', 'H', 'I'],
        ]);

        $excel->save();
    }
}

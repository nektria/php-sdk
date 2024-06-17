<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Generator;

readonly class CsvFileReader
{
    /**
     * @param resource $file
     */
    public function __construct(
        private $file,
        private string $separator
    ) {
    }

    /**
     * @return Generator<string[]>
     */
    public function readLine(): Generator
    {
        $row = fgetcsv($this->file, separator: $this->separator);
        while ($row !== false) {
            yield $row;

            $row = fgetcsv($this->file, separator: $this->separator);
        }
    }
}

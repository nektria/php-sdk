<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Generator;

readonly class CsvFileReader extends FileReader
{
    /**
     * @param resource $file
     */
    public function __construct(
        mixed $file,
        private string $separator
    ) {
        parent::__construct($file);
    }

    /**
     * @return Generator<string[]>
     */
    public function csvLines(): Generator
    {
        $row = fgetcsv($this->file, separator: $this->separator);
        while ($row !== false) {
            yield $row;

            $row = fgetcsv($this->file, separator: $this->separator);
        }

        return null;
    }
}

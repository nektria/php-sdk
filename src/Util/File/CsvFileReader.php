<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Generator;

readonly class CsvFileReader extends FileReader
{
    public function __construct(
        string $file,
        private string $separator
    ) {
        parent::__construct($file);
    }

    /**
     * @param array<int|float|string|bool> $content
     */
    public function appendData(array $content): void
    {
        fputcsv($this->resource, $content);
    }

    /**
     * @return Generator<string[]>
     */
    public function csvLines(): Generator
    {
        $row = fgetcsv($this->resource, separator: $this->separator);
        while ($row !== false) {
            yield $row;

            $row = fgetcsv($this->resource, separator: $this->separator);
        }

        return null;
    }
}

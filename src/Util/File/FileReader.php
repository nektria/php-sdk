<?php

declare(strict_types=1);

namespace Nektria\Util\File;

use Generator;

readonly class FileReader
{
    /**
     * @param resource $file
     */
    public function __construct(
        protected mixed $file,
    ) {
    }

    /**
     * @return Generator<string>
     */
    public function lines(): Generator
    {
        $row = fgets($this->file);
        while ($row !== false) {
            yield $row;

            $row = fgets($this->file);
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Document;

class FileDocument implements Document
{
    private int | bool $size;

    public function __construct(
        public readonly string $file,
        public readonly string $filename,
        public readonly string $mime,
    ) {
        $this->size = filesize($file);
    }

    public function toArray(string $model): mixed
    {
        return [
            'path' => $this->file,
            'name' => $this->filename,
            'size' => $this->size,
            'mime' => $this->mime,
        ];
    }
}

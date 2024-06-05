<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

readonly class FileDocument extends Document
{
    private int | bool $size;

    public function __construct(
        public string $file,
        public string $filename,
        public string $mime,
    ) {
        $this->size = filesize($file);
    }

    public function toArray(ContextService $context): mixed
    {
        return [
            'path' => $this->file,
            'name' => $this->filename,
            'size' => $this->size,
            'mime' => $this->mime,
        ];
    }
}

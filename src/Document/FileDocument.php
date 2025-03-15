<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

use function count;

use const DIRECTORY_SEPARATOR;

readonly class FileDocument extends Document
{
    private int | bool $size;

    public function __construct(
        public string $file,
        public string $filename,
        public string $mime,
        public ?int $maxAge = null,
    ) {
        $this->size = filesize($file);
    }

    public static function fromFile(string $file): self
    {
        $parts = explode(DIRECTORY_SEPARATOR, $file);
        $filename = $parts[count($parts) - 1];
        $mime = mime_content_type($file);
        if ($mime === false) {
            $mime = 'application/octet-stream';
        }

        return new self(
            file: $file,
            filename: $filename,
            mime: $mime,
        );
    }

    public function toArray(ContextService $context): array
    {
        return [
            'path' => $this->file,
            'name' => $this->filename,
            'size' => $this->size,
            'mime' => $this->mime,
        ];
    }
}

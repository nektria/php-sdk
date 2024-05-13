<?php

declare(strict_types=1);

namespace Nektria\Util;

use RuntimeException;

class FileUtil
{
    public static function read(string $file): string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException('Cannot read file');
        }

        return $content;
    }

    public static function write(string $file, string $content): void
    {
        if (file_put_contents($file, $content) === false) {
            throw new RuntimeException('Cannot write file');
        }
    }

    /**
     * @param string[][] $data
     */
    public static function createCsv(array $data, ?string $filename = null): string
    {
        $filename ??= StringUtil::uuid4();
        $path = "/app/tmp/{$filename}.csv";
        touch($path);
        $temp = fopen($path, 'wb');

        if ($temp === false) {
            throw new RuntimeException('Cannot create temporary file');
        }

        foreach ($data as $fields) {
            fputcsv($temp, $fields);
        }

        fwrite($temp, '');
        fclose($temp);

        return $path;
    }
}

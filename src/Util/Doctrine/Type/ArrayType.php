<?php

declare(strict_types=1);

namespace Nektria\Util\Doctrine\Type;

use Nektria\Util\JsonUtil;

/**
 * @extends JsonType<array<mixed>>
 */
class ArrayType extends JsonType
{
    protected function getTypeName(): string
    {
        return 'array_item';
    }

    /**
     * @return array<mixed>
     */
    protected function convertToPhp(string $databaseValue): array
    {
        return JsonUtil::decode($databaseValue);
    }

    /**
     * @param array<mixed> $phpValue
     */
    protected function convertToDatabase($phpValue): string
    {
        return JsonUtil::encode($phpValue);
    }
}

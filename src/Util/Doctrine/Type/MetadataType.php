<?php

declare(strict_types=1);

namespace Nektria\Util\Doctrine\Type;

use Nektria\Dto\Metadata;
use Nektria\Util\JsonUtil;

/**
 * @extends JsonType<Metadata>
 */
class MetadataType extends JsonType
{
    /**
     * @param Metadata $phpValue
     */
    protected function convertToDatabase($phpValue): string
    {
        return JsonUtil::encode($phpValue->data());
    }

    /**
     * @return Metadata
     */
    protected function convertToPhp(string $databaseValue): array
    {
        return new Metadata(JsonUtil::decode($databaseValue));
    }

    protected function getTypeName(): string
    {
        return 'metadata';
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

readonly abstract class Document
{
    /**
     * @return mixed[]
     */
    abstract public function toArray(ContextService $context): array;
}

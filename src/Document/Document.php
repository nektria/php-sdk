<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

interface Document
{
    public function toArray(ContextService $context): mixed;
}

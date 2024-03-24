<?php

declare(strict_types=1);

namespace Nektria\Exception;

use RuntimeException;

class ResourceNotFoundException extends RuntimeException
{
    public function __construct(string $resourceType)
    {
        parent::__construct("{$resourceType} not found.");
    }
}

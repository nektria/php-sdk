<?php

declare(strict_types=1);

namespace Nektria\Exception;

class ResourceNotFoundException extends NektriaRuntimeException
{
    public function __construct(string $resourceType)
    {
        parent::__construct("{$resourceType} not found.");
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Nektria\Dto\RequestResponse;
use RuntimeException;

class RequestClientException extends RuntimeException
{
    public function __construct(
        public readonly RequestResponse $response
    ) {
        parent::__construct($response->body, $response->status);
    }
}

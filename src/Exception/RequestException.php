<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Nektria\Dto\RequestResponse;
use RuntimeException;

class RequestException extends RuntimeException
{
    public function __construct(
        private readonly RequestResponse $response
    ) {
        parent::__construct('Request Failed', $response->status);
    }

    public function response(): RequestResponse
    {
        return $this->response;
    }
}

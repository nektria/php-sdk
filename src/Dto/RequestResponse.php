<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Util\JsonUtil;
use Throwable;

class RequestResponse
{
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly int $status,
        public readonly string $body
    ) {
    }

    public function json(): mixed
    {
        try {
            return JsonUtil::decode($this->body);
        } catch (Throwable) {
            return [
                '_response' => $this->body,
            ];
        }
    }

    public function isSuccessful(): bool
    {
        return $this->status < 400;
    }
}

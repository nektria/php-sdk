<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Util\JsonUtil;
use Throwable;

class RequestResponse
{
    /**
     * @param array<string, string> $requestHeaders
     * @param array<string, string[]> $responseHeaders
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly int $status,
        public readonly string $body,
        public readonly array $requestHeaders,
        public readonly array $responseHeaders,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->status < 400;
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
}

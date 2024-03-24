<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Nektria\Util\JsonUtil;
use RuntimeException;

class RequestClientException extends RuntimeException
{
    public function __construct(
        private readonly string $content,
        private readonly int $status
    ) {
        parent::__construct($content, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function response(): mixed
    {
        return JsonUtil::decode($this->content);
    }
}

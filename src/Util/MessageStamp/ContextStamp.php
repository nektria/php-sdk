<?php

declare(strict_types=1);

namespace Nektria\Util\MessageStamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ContextStamp implements StampInterface
{
    public function __construct(
        public readonly string $traceId,
        public readonly ?string $tenantId
    ) {
    }
}

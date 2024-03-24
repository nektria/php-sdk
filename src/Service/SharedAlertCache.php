<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

use function count;

/**
 * @extends SharedRedisCache<string[]>
 */
class SharedAlertCache extends SharedRedisCache
{
    /**
     * @return string[]
     */
    public function read(string $channel): array
    {
        return $this->getItem($channel) ?? [];
    }

    public function addMessage(string $channel, string $message): void
    {
        $items = $this->read($channel);

        if (count($items) >= 100) {
            array_shift($items);
        }

        $items[] = $message;
        $this->setItem($channel, $items, 3600);
    }
}

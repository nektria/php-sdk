<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

use function count;

/**
 * @phpstan-import-type AlertMessage from AlertService
 * @extends SharedRedisCache<AlertMessage[]>
 */
class SharedDiscordCache extends SharedRedisCache
{
    /**
     * @return AlertMessage[]
     */
    public function read(string $channel): array
    {
        return $this->getItem($channel) ?? [];
    }

    /**
     * @param AlertMessage $message
     */
    public function addMessage(string $channel, array $message): void
    {
        $items = $this->read($channel);

        if (count($items) >= 100) {
            array_shift($items);
        }

        $items[] = $message;
        $this->setItem($channel, $items, 3600);
    }

    public function remove(string $channel): void
    {
        $this->removeItem($channel);
    }
}

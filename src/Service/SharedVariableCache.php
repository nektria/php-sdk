<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedRedisCache;

/**
 * @extends SharedRedisCache<string|bool|int|float>
 */
class SharedVariableCache extends SharedRedisCache
{
    public const DEFAULT = '1';

    public function deleteKey(string $key): void
    {
        $this->removeItem($key);
    }

    public function executeIfNotExists(string $key, callable $callback, int $lifetime = 300): void
    {
        if ($this->hasKey($key)) {
            return;
        }

        $this->saveKey($key, $lifetime);
        $callback();
    }

    public function hasKey(string $key): bool
    {
        $value = $this->getItem($key);

        return $value !== null;
    }

    public function readInt(string $key, int $default = 0): int
    {
        return (int) ($this->getItem($key) ?? $default);
    }

    public function readString(string $key, string $default = ''): string
    {
        return (string) ($this->getItem($key) ?? $default);
    }

    public function refreshKey(string $key, int $lifetime = 300): bool
    {
        $exists = !$this->hasKey($key);
        $value = $this->getItem($key) ?? self::DEFAULT;
        $this->setItem($key, $value, $lifetime);

        return $exists;
    }

    public function saveInt(string $key, int $value): void
    {
        $this->setItem($key, $value, 604800);
    }

    public function saveKey(string $key, int $lifetime = 300): void
    {
        $this->setItem($key, self::DEFAULT, $lifetime);
    }

    public function saveString(string $key, string $value, int $ttl = 300): void
    {
        $this->setItem($key, $value, 604800);
    }
}

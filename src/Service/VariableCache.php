<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\InternalRedisCache;

/**
 * @extends InternalRedisCache<string|bool|int|float>
 */
class VariableCache extends InternalRedisCache
{
    public const DEFAULT = '1';

    public function saveKey(string $key, int $lifetime = 300): void
    {
        $this->setItem($key, self::DEFAULT, $lifetime);
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

    public function refreshKey(string $key, int $lifetime = 300): void
    {
        $value = $this->getItem($key) ?? self::DEFAULT;
        $this->setItem($key, $value, $lifetime);
    }

    public function deleteKey(string $key): void
    {
        $this->removeItem($key);
    }

    public function saveInt(string $key, int $value, int $lifetime = 300): void
    {
        $this->setItem($key, $value, $lifetime);
    }

    /**
     * @param string[] $keys
     * @return array<string, int>
     */
    public function readMultipleInt(array $keys): array
    {
        $values = $this->getItems($keys);
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = (int) ($values[$key] ?? 0);
        }

        return $result;
    }

    public function readInt(string $key, int $default = 0): int
    {
        return (int) ($this->getItem($key) ?? $default);
    }

    public function saveString(string $key, string $value): void
    {
        $this->setItem($key, $value, 604800);
    }

    public function readString(string $key, string $default = ''): string
    {
        return (string) ($this->getItem($key) ?? $default);
    }
}

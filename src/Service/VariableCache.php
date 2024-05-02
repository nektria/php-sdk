<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\InternalRedisCache;

/**
 * @extends InternalRedisCache<string|bool|int|float>
 */
class VariableCache extends InternalRedisCache
{
    public const DEFAULT_TTL = 300;

    public const DEFAULT = '1';

    public function saveKey(string $key, int $ttl = self::DEFAULT_TTL): void
    {
        $this->setItem($key, self::DEFAULT, $ttl);
    }

    public function executeIfNotExists(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): void
    {
        if ($this->hasKey($key)) {
            return;
        }

        $this->saveKey($key, $ttl);
        $callback();
    }

    public function hasKey(string $key): bool
    {
        $value = $this->getItem($key);

        return $value !== null;
    }

    public function refreshKey(string $key, int $ttl = self::DEFAULT_TTL): bool
    {
        $isNew = !$this->hasKey($key);
        $value = $this->getItem($key) ?? self::DEFAULT;
        if ($isNew) {
            $this->setItem($key, $value, $ttl);
        }

        return $isNew;
    }

    public function deleteKey(string $key): void
    {
        $this->removeItem($key);
    }

    public function saveInt(string $key, int $value, int $ttl = self::DEFAULT_TTL): void
    {
        $this->setItem($key, $value, $ttl);
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

    public function saveString(string $key, string $value, int $ttl = self::DEFAULT_TTL): void
    {
        $this->setItem($key, $value, $ttl);
    }

    public function readString(string $key, string $default = ''): string
    {
        return (string) ($this->getItem($key) ?? $default);
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Dto\Clock;
use Throwable;

/**
 * @template T
 */
abstract class SharedRedisCache extends RedisCache
{
    public function __construct(
        string $redisDsn,
        string $env,
    ) {
        parent::__construct($redisDsn, $env, 'shared');
    }

    /**
     * @return T|null
     */
    protected function getItem(string $key): mixed
    {
        try {
            $item = $this->init()->get("{$this->fqn}:{$key}");

            if ($item === false) {
                return null;
            }

            return unserialize($item, ['allowed_classes' => true]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param T $item
     */
    protected function setItem(string $key, $item, Clock | int $ttl = 300): void
    {
        if ($ttl instanceof Clock) {
            $ttl = $ttl->diff(Clock::new());
        }

        try {
            $this->init()->set("{$this->fqn}:{$key}", serialize($item), $ttl);
        } catch (Throwable) {
        }
    }

    protected function removeItem(string $key): void
    {
        try {
            $this->init()->del("{$this->fqn}:{$key}");
        } catch (Throwable) {
        }
    }

    public function empty(): void
    {
        try {
            $this->init()->eval("for _,k in ipairs(redis.call('keys','{$this->fqn}:*')) do redis.call('del',k) end");
        } catch (Throwable) {
        }
    }
}

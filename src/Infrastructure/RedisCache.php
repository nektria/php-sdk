<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Exception\NektriaException;
use Redis;
use Throwable;

use function count;

abstract class RedisCache
{
    public readonly string $fqn;

    private static ?Redis $connection = null;

    private readonly string $redisDsn;

    public function __construct(
        string $redisDsn,
        string $env,
        string $prefix
    ) {
        $parts = explode('\\', static::class);
        $name = substr(end($parts), 0, -5);
        $this->fqn = "{$prefix}_{$name}_{$env}";
        $this->redisDsn = $redisDsn;
    }

    public function decr(string $key): void
    {
        try {
            $this->init()->decr($key);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function exec(): void
    {
        try {
            $this->init()->exec();
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function incr(string $key): void
    {
        try {
            $this->init()->incr($key);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function multi(): void
    {
        try {
            $this->init()->multi();
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @return int[]
     */
    public function size(): array
    {
        try {
            $it = null;
            $count = 0;
            $size = 0;

            do {
                $keys = $this->init()->scan($it, "{$this->fqn}:*", 1000);

                if ($keys !== false) {
                    $count += count($keys);
                    if ($size === 0 && count($keys) > 0) {
                        try {
                            $memoryUsage = $this->init()->rawCommand('MEMORY', 'USAGE', $keys[0]);
                            $size += $memoryUsage;
                        } catch (Throwable) {
                        }
                    }
                }
            } while ((int) $it > 0);

            return [$count, $size * $count];
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    protected function init(): Redis
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        try {
            $parts = parse_url($this->redisDsn);
            $redis = new Redis();

            $redis->pconnect(
                $parts['host'] ?? 'localhost',
                $parts['port'] ?? 6379,
                0,
                (string) getenv('HOSTNAME'),
            );

            if (isset($parts['pass'])) {
                $redis->auth($parts['pass']);
            }

            $redis->select(0);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        self::$connection = $redis;

        return self::$connection;
    }
}

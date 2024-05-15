<?php

declare(strict_types=1);

namespace Nektria\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

class LockMessageService
{
    private ?LockFactory $lock;

    /** @var array<string, LockInterface|SharedLockInterface> */
    private array $locks = [];

    public function __construct(
        private readonly string $redisDsn
    ) {
    }

    public function acquire(string $name, float $ttl = 300): void
    {
        $this->locks[$name] = $this->init()->createLock($name, $ttl);
        if ($this->locks[$name]->isAcquired()) {
            throw new RecoverableMessageHandlingException('');
        }
        $this->locks[$name]->acquire(true);
    }

    public function create(string $name, float $ttl = 300): LockInterface
    {
        return $this->init()->createLock($name, $ttl);
    }

    public function release(string $name): void
    {
        if (isset($this->locks[$name])) {
            $this->locks[$name]->release();
            unset($this->locks[$name]);
        }
    }

    public function releaseAll(): void
    {
        foreach ($this->locks as $name => $lock) {
            $lock->release();
            unset($this->locks[$name]);
        }
    }

    public function wait(string $name, float $ttl = 300): void
    {
        $realTtl = $ttl / 1000;
        $origin = microtime(true);
        $this->locks[$name] = $this->init()->createLock($name, $ttl);
        while ($this->locks[$name]->isAcquired()) {
            $timesamp = microtime(true) - $origin;
            if ($timesamp > $realTtl) {
                break;
            }
        }
        $this->locks[$name]->acquire(true);
    }

    private function init(): LockFactory
    {
        $this->lock ??= new LockFactory(new RedisStore(RedisAdapter::createConnection($this->redisDsn)));

        return $this->lock;
    }
}

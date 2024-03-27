<?php

declare(strict_types=1);

namespace Nektria\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nektria\Dto\Clock;

abstract class EventEntity implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'micro_clock')]
    protected Clock $timestamp;

    #[ORM\Column(type: 'guid')]
    protected string $tenantId;

    public function __construct(string $tenantId)
    {
        $this->tenantId = $tenantId;
        $this->timestamp = Clock::new();
    }

    public function id(): string
    {
        return (string) $this->timestamp;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function timestamp(): Clock
    {
        return $this->timestamp;
    }
}

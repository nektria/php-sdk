<?php

declare(strict_types=1);

namespace Nektria\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nektria\Dto\Clock;
use Nektria\Util\Annotation\IgnoreProperty;
use Nektria\Util\StringUtil;

abstract class Entity implements EntityInterface
{
    #[IgnoreProperty]
    #[ORM\Column(type: 'clock')]
    public protected(set) Clock $createdAt;

    #[IgnoreProperty]
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public protected(set) string $id;

    #[IgnoreProperty]
    #[ORM\Column(type: 'clock')]
    public protected(set) Clock $updatedAt;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? StringUtil::uuid4();
        $this->createdAt = Clock::now();
        $this->updatedAt = $this->createdAt;
    }

    #[\Deprecated]
    public function createdAt(): Clock
    {
        return $this->createdAt;
    }

    #[\Deprecated]
    public function id(): string
    {
        return $this->id;
    }

    public function refresh(): void
    {
        $this->updatedAt = Clock::now();
    }

    #[\Deprecated]
    public function updatedAt(): Clock
    {
        return $this->updatedAt;
    }
}

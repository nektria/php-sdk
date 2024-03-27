<?php

declare(strict_types=1);

namespace Nektria\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nektria\Util\StringUtil;

abstract class Entity implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    protected string $id = '';

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? StringUtil::uuid4();
    }

    public function id(): string
    {
        return $this->id;
    }
}

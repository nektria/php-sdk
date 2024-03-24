<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Nektria\Entity\Entity;
use Throwable;

/**
 * @template T of Entity
 */
abstract class WriteModel
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return class-string<T>
     */
    abstract protected function getClassName(): string;

    protected function saveEntity(Entity $domain): void
    {
        $this->manager->persist($domain);
        $this->manager->flush();
        $this->manager->detach($domain);
    }

    protected function deleteEntity(Entity $domain): void
    {
        try {
            $this->manager->remove($domain);
            $this->manager->flush();
        } catch (Throwable) {
            $domain = $this->findEntity($domain->id());
            if ($domain !== null) {
                $this->manager->remove($domain);
                $this->manager->flush();
            }
        }
    }

    protected function findEntity(string $id): ?Entity
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param mixed[] $criteria
     */
    protected function findOneBy(array $criteria): ?Entity
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * @param mixed[] $criteria
     * @param mixed[]|null $orderBy
     * @return Entity[]
     */
    protected function findBy(array $criteria, ?array $orderBy = null, int $limit = 10000, int $offset = 0): array
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    public function manager(): EntityManagerInterface
    {
        return $this->manager;
    }

    /**
     * @return EntityRepository<T>
     */
    protected function getRepository(): EntityRepository
    {
        return $this->manager->getRepository($this->getClassName());
    }
}

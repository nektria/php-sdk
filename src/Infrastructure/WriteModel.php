<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Nektria\Entity\EntityInterface;
use Throwable;

/**
 * @template T of EntityInterface
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

    /**
     * @param T $domain
     */
    protected function saveEntity(EntityInterface $domain): void
    {
        $domain->refresh();
        $this->manager->persist($domain);
        $this->manager->flush();
        $this->manager->detach($domain);
    }

    /**
     * @param T $domain
     */
    protected function deleteEntity(EntityInterface $domain): void
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

    /**
     * @return T|null
     */
    protected function findEntity(string $id): ?EntityInterface
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param mixed[] $criteria
     * @return T|null
     */
    protected function findOneBy(array $criteria): ?EntityInterface
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * @param mixed[] $criteria
     * @param mixed[]|null $orderBy
     * @return T[]
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

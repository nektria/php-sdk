<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Nektria\Entity\EntityInterface;
use Nektria\Entity\EventEntity;
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

    public function manager(): EntityManagerInterface
    {
        return $this->manager;
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
     * @param mixed[] $criteria
     * @param mixed[]|null $orderBy
     * @return T[]
     */
    protected function findBy(array $criteria, ?array $orderBy = null, int $limit = 10000, int $offset = 0): array
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
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
     * @return class-string<T>
     */
    abstract protected function getClassName(): string;

    /**
     * @return EntityRepository<T>
     */
    protected function getRepository(): EntityRepository
    {
        return $this->manager->getRepository($this->getClassName());
    }

    /**
     * @param T $domain
     */
    protected function saveEntity(EntityInterface $domain): void
    {
        try {
            $domain->refresh();
            $this->manager->persist($domain);
            $this->manager->flush();
            $this->manager->detach($domain);
        } catch (Throwable $e) {
            $this->manager()->getConnection()->rollBack();
            if (
                $domain instanceof EventEntity
                && str_contains($e->getMessage(), 'duplicate key value violates unique constraint')
            ) {
                $domain->fixTimeStamp();
                $this->manager->persist($domain);
                $this->manager->flush();
                $this->manager->detach($domain);
            }
        }
    }
}

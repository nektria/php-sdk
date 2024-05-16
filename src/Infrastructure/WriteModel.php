<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nektria\Entity\EntityInterface;
use Nektria\Entity\EventEntity;
use Nektria\Exception\NektriaException;
use RuntimeException;
use Throwable;

/**
 * @template T of EntityInterface
 */
abstract class WriteModel
{
    private EntityManager $manager;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry
    ) {
        $manager = $this->managerRegistry->getManager();
        if (!($manager instanceof EntityManager)) {
            throw new RuntimeException('Unable to restart the manager.');
        }
        $this->manager = $manager;
    }

    public function manager(): EntityManager
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
            $this->resetManager();

            if (
                $domain instanceof EventEntity
                && str_contains($e->getMessage(), 'duplicate key value violates unique constraint')
            ) {
                try {
                    $domain->fixTimeStamp();
                    $this->manager->persist($domain);
                    $this->manager->flush();
                    $this->manager->detach($domain);

                    return;
                } catch (Throwable) {
                    $this->resetManager();

                    throw NektriaException::new($e);
                }
            }

            throw NektriaException::new($e);
        }
    }

    private function resetManager(): void
    {
        $this->managerRegistry->resetManager();
        $manager = $this->managerRegistry->getManager();
        if (!($manager instanceof EntityManager)) {
            throw new RuntimeException('Unable to restart the manager.');
        }

        $this->manager = $manager;
    }
}

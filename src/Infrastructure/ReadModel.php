<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Nektria\Document\Document;
use Nektria\Document\DocumentCollection;
use Nektria\Dto\Clock;
use Nektria\Exception\NektriaException;
use Nektria\Util\StringUtil;
use Throwable;

use function count;
use function is_array;

/**
 * @template T of Document
 */
abstract class ReadModel
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
     * @param array<string, string|int|float|bool|null> $params
     * @param string[] $groupBy
     * @return mixed[]|null
     */
    protected function getRawResult(string $sql, array $params = [], array $groupBy = []): ?array
    {
        try {
            $results = $this->getRawResults($sql, $params, $groupBy);

            return $results[array_key_first($results)] ?? null;
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $params
     * @param string[] $groupBy
     * @return T|null
     */
    protected function getResult(string $sql, array $params = [], array $groupBy = []): ?Document
    {
        $result = $this->getRawResult($sql, $params, $groupBy);

        if ($result === null) {
            return null;
        }

        return $this->buildDocument($result);
    }

    /**
     * @param array<string, Clock|string|int|float|bool|string[]|null> $params
     * @param string[] $groupBy
     * @return mixed[]
     */
    protected function getRawResults(string $sql, array $params = [], array $groupBy = []): array
    {
        $sql = StringUtil::trim($sql);
        if (!str_starts_with($sql, 'SELECT')) {
            $sql = "{$this->source()} {$sql}";
        }

        $newParams = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = "'" . implode("','", $value) . "'";
                $sql = str_replace(":$key", $value, $sql);
            } else {
                $newParams[$key] = $value;
            }
        }

        try {
            $query = $this->manager->getConnection()->prepare($sql);
            foreach ($newParams as $key => $value) {
                $query->bindValue($key, $value);
            }
            $results = $query->executeQuery($newParams)->fetchAllAssociative();

            if (count($groupBy) > 0) {
                $newResults = [];
                foreach ($results as $item) {
                    $key = '';
                    foreach ($groupBy as $group) {
                        $key .= $item[$group];
                    }

                    $newResults[$key] ??= [];
                    $newResults[$key][] = $item;
                }

                $results = $newResults;
            }

            return $results;
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @param array<string, Clock|string|int|float|bool|string[]|null> $params
     * @param string[] $groupBy
     * @return DocumentCollection<T>
     */
    protected function getResults(string $sql, array $params = [], array $groupBy = []): DocumentCollection
    {
        $results = $this->getRawResults($sql, $params, $groupBy);
        $parsed = [];

        foreach ($results as $item) {
            $parsed[] = $this->buildDocument($item);
        }

        return new DocumentCollection($parsed);
    }

    /**
     * @param array<string, mixed> $params
     * @return T
     */
    abstract protected function buildDocument(array $params): Document;

    abstract protected function source(): string;
}

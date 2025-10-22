<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\ArrayDocument;
use Nektria\Document\Document;
use Nektria\Document\DocumentCollection;

/**
 * @extends ReadModel<ArrayDocument>
 */
class ArrayDocumentReadModel extends ReadModel
{
    public function fixMigrations(): void
    {
        $this->getRawResult('
           ALTER TABLE doctrine_migration_versions ALTER executed_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        ');
        $this->getRawResult('COMMENT ON COLUMN doctrine_migration_versions.executed_at IS \'(DC2Type:clock)\'');
    }

    /**
     * @param array<string, string|int|float|bool|null> $filters
     * @return DocumentCollection<ArrayDocument>
     */
    public function readCustom(string $table, string $order, int $page, int $limit, array $filters): DocumentCollection
    {
        $table = str_replace([';', ' '], '', $table);
        $order = str_replace([';', ' '], '', $order);

        $query = '';
        foreach (array_keys($filters) as $filter) {
            $filter = str_replace([';', ' ', "'", '"'], '', $filter);
            $query .= " AND {$filter}=:{$filter}";
        }

        return $this->getResults("
            SELECT *
            FROM {$table}
            WHERE true {$query}
            ORDER BY {$order} DESC
            LIMIT :limit OFFSET :offset
        ", [...$filters, ...[
            'offset' => ($page - 1) * $limit,
            'limit' => $limit,
        ]]);
    }

    /**
     * @return DocumentCollection<ArrayDocument>
     */
    public function readMigrations(): DocumentCollection
    {
        return $this->getResults('
            SELECT *
            FROM doctrine_migration_versions
            ORDER BY version
        ');
    }

    protected function buildDocument(array $params): Document
    {
        return new ArrayDocument($params);
    }

    protected function source(): string
    {
        return '';
    }
}

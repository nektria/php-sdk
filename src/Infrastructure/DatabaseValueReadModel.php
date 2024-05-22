<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\DatabaseValue;
use Nektria\Document\Document;
use Nektria\Document\DocumentCollection;

/**
 * @extends ReadModel<DatabaseValue>
 */
class DatabaseValueReadModel extends ReadModel
{
    /**
     * @return DocumentCollection<DatabaseValue>
     */
    public function readCustom(string $table, string $order, int $page): DocumentCollection
    {
        $table = str_replace([';', ' '], '', $table);
        $order = str_replace([';', ' '], '', $order);

        return $this->getResults("
            SELECT *
            FROM {$table}
            ORDER BY {$order} DESC
            LIMIT 20 OFFSET :offset
        ", [
            'offset' => ($page - 1) * 20,
        ]);
    }

    /**
     * @return DocumentCollection<DatabaseValue>
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
        return new DatabaseValue($params);
    }

    protected function source(): string
    {
        return '';
    }
}

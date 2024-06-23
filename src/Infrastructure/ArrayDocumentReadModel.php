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
     * @return DocumentCollection<ArrayDocument>
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

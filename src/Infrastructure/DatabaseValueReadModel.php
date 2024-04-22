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

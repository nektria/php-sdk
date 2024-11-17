<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Document\__ENTITY__;
use Nektria\Document\Document;
use Nektria\Dto\Clock;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Infrastructure\ReadModel;

/**
 * @extends ReadModel<__ENTITY__>
 */
class __ENTITY__ReadModel extends ReadModel
{
    public function opt(string $id): ?__ENTITY__
    {
        return $this->getResult(
            'WHERE id=:id',
            [
                'id' => $id
            ],
        );
    }

    public function read(string $id): __ENTITY__
    {
        $data = $this->getResult(
            'WHERE id=:id',
            [
                'id' => $id
            ],
        );

        if ($data === null) {
            throw new ResourceNotFoundException('__ENTITY__', $id);
        }

        return $data;
    }

    protected function buildDocument(array $params): Document
    {
        return new __ENTITY__(
            id: $params['id'],
            createdAt: Clock::fromString($params['created_at']),
            updatedAt: Clock::fromString($params['updated_at']),
        );
    }

    protected function source(): string
    {
        return '
            SELECT *
            FROM __ENTITY_SC__
        ';
    }
}

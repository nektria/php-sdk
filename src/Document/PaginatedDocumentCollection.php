<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

/**
 * @template T of Document
 */
readonly class PaginatedDocumentCollection extends Document
{
    /**
     * @param DocumentCollection<T>|NewDocumentCollection<T> $items
     */
    public function __construct(
        public DocumentCollection|NewDocumentCollection $items,
        public int $page,
        public int $pageSize,
        public int $total,
    ) {
        parent::__construct();
    }

    protected function toArray(?ContextService $context): array
    {
        return [
            'pageSize' => $this->pageSize,
            'items' => $this->items->toArray($context)['items'],
            'page' => $this->page,
            'total' => $this->total,
            'totalPages' => (int) ceil($this->total / $this->pageSize),
        ];
    }
}

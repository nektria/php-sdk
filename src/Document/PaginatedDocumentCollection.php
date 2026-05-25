<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

/**
 * @template T of Document
 */
readonly class PaginatedDocumentCollection extends Document
{
    public int $pageSize;

    /**
     * @param DocumentCollection<T> $items
     */
    public function __construct(
        public DocumentCollection $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {
        parent::__construct();
        $this->pageSize = $items->count();
    }

    protected function toArray(?ContextService $context): array
    {
        return [
            'pageSize' => $this->pageSize,
            'items' => $this->items->toArray($context)['items'],
            'page' => $this->page,
            'total' => $this->total,
            'totalPages' => $this->totalPages,
        ];
    }
}

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
     * @param NewDocumentCollection<T> $items
     */
    public function __construct(
        public NewDocumentCollection $items,
        public int $page,
        public int $pageSize,
        public int $totalItems,
    ) {
        parent::__construct();
    }

    protected function toArray(?ContextService $context): array
    {
        $data = $this->items->toArray($context);
        $data['page'] = $this->page;
        $data['pageSize'] = $this->pageSize;
        $data['totalItems'] = $this->totalItems;
        $data['totalPages'] = (int) ceil($this->totalItems / $this->pageSize);

        return $data;
    }
}

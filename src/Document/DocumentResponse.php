<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;
use Symfony\Component\HttpFoundation\JsonResponse;

class DocumentResponse extends JsonResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly Document $document,
        ContextService $context,
        int $status = 200,
        array $headers = []
    ) {
        if ($this->document instanceof ThrowableDocument) {
            parent::__construct($this->document->toArray($context), $this->document->status);
        } elseif (
            $this->document instanceof DocumentCollection
            && (
                $context->context() === ContextService::PUBLIC_V2
            )
        ) {
            parent::__construct([
                'list' => $this->document->toArray($context),
            ], $status);
        } else {
            parent::__construct($this->document->toArray($context), $status);
        }

        $this->headers->add($headers);
    }
}

<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;
use Symfony\Component\HttpFoundation\JsonResponse;

class DocumentResponse extends JsonResponse
{
    private Document $document;

    public function __construct(Document $document, ContextService $context, int $status = 200)
    {
        if ($document instanceof ThrowableDocument) {
            parent::__construct($document->toArray($env), $document->status);
        } elseif (
            $document instanceof DocumentCollection
            && (
                $context->context() === ContextService::PUBLIC_V2
            )
        ) {
            parent::__construct([
                'list' => $document->toArray($context->context())
            ], $status);
        } else {
            parent::__construct($document->toArray($context->context()), $status);
        }

        $this->document = $document;
    }

    public function document(): Document
    {
        return $this->document;
    }
}

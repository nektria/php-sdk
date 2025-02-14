<?php

declare(strict_types=1);

namespace Nektria\Console\Mercure;

use Nektria\Console\Console;
use Nektria\Document\ArrayDocument;
use Nektria\Service\ContextService;
use Nektria\Service\SocketService;

class MercureTestConsole extends Console
{
    public function __construct(
        private readonly SocketService $socketService,
        private readonly ContextService $contextService,
    ) {
        parent::__construct('admin:mercure:test');
    }

    protected function play(): void
    {
        $this->socketService->publish('mercure.test', new ArrayDocument([
            'project' => $this->contextService->project(),
            'message' => 'Hello'
        ]));
    }
}

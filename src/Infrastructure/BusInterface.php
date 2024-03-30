<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\Document;
use Nektria\Message\Command;
use Nektria\Message\Event;
use Nektria\Message\Query;

interface BusInterface
{
    /**
     * @template T of Document
     * @param Query<T> $query
     */
    public function dispatchQuery(Query $query): Document;

    public function dispatchCommand(Command $command, ?string $transport = null): void;

    public function dispatchEvent(Event $event): void;

    public function addEvent(Event $event): void;

    public function dispatchDelayedEvents(): void;
}

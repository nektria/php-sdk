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
     * @return T
     */
    public function dispatchQuery(Query $query): Document;

    /**
     * @param array{
     *     currentTry: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    public function dispatchCommand(
        Command $command,
        ?string $transport = null,
        ?int $msDelay = null,
        ?array $retryOptions = null
    ): void;

    public function dispatchEvent(Event $event): void;

    public function addDelayedEvent(Event $event): void;

    public function dispatchDelayedEvents(): void;
}

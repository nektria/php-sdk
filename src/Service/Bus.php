<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Document;
use Nektria\Exception\NektriaException;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\UserServiceInterface;
use Nektria\Message\Command;
use Nektria\Message\Event;
use Nektria\Message\Query;
use Nektria\Util\Annotation\RolesRequired;
use Nektria\Util\MessageStamp\ContextStamp;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

use function count;

class Bus implements BusInterface
{
    /** @var Event[] */
    private array $delayedEvents;

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly ContextService $contextService,
        private readonly UserServiceInterface $userRoleValidator,
    ) {
        $this->delayedEvents = [];
    }

    /**
     * @template T of Document
     * @param Query<T> $query
     * @throws Throwable
     */
    final public function dispatchQuery(Query $query): Document
    {
        try {
            $this->validateAccess($query);

            $result = $this->bus->dispatch($query, [
                new ContextStamp(
                    $this->contextService->context(),
                    $this->contextService->traceId(),
                    $this->contextService->tenantId(),
                    $this->contextService->userId()
                )
            ])->last(HandledStamp::class);

            if ($result === null) {
                throw new RuntimeException('Query does not return a Document');
            }

            return $result->getResult();
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw $previous;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    final public function dispatchCommand(Command $command): void
    {
        try {
            $this->bus->dispatch($command, [
                new ContextStamp(
                    $this->contextService->context(),
                    $this->contextService->traceId(),
                    $this->contextService->tenantId(),
                    $this->contextService->userId()
                )
            ]);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw $previous;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    final public function dispatchEvent(Event $event): void
    {
        try {
            $this->bus->dispatch($event, [
                new ContextStamp(
                    $this->contextService->context(),
                    $this->contextService->traceId(),
                    $this->contextService->tenantId(),
                    $this->contextService->userId(),
                )
            ]);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw $previous;
            }

            throw $e;
        }
    }

    final public function dispatchDelayedEvents(): void
    {
        foreach ($this->delayedEvents as $event) {
            try {
                $this->dispatchEvent($event);
            } catch (Throwable) {
            }
        }
        $this->delayedEvents = [];
    }

    final public function addEvent(Event $event): void
    {
        $this->delayedEvents[] = $event;
    }

    /**
     * @template T of Document
     * @param Command|Query<T> $message
     */
    private function validateAccess(Command | Query $message): void
    {
        try {
            $reflectionClass = new ReflectionClass($message);
            $attributes = $reflectionClass->getAttributes(RolesRequired::class);

            if (count($attributes) === 0) {
                $clzz = $reflectionClass->getName();

                throw new RuntimeException("Query '{$clzz}' does not have RolesRequired attribute");
            }

            /** @var RolesRequired $instance */
            $instance = $attributes[0]->newInstance();

            $this->userRoleValidator->validateRole($instance->roles);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }
}

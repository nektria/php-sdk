<?php

declare(strict_types=1);

namespace Nektria\Document;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Nektria\Exception\NektriaException;
use Nektria\Service\ContextService;
use Nektria\Util\ArrayUtil;
use Traversable;

use function count;

/**
 * @implements ArrayAccess<int, T>
 * @implements IteratorAggregate<int, T>
 * @template T of Document
 */
readonly class DocumentCollection extends Document implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @param T[] $items
     */
    public function __construct(
        private array $items
    ) {
    }

    /**
     * @template X of Document
     * @param DocumentCollection<X> $a
     * @param DocumentCollection<X> $b
     * @return DocumentCollection<X>
     */
    public static function merge(self $a, self $b): self
    {
        return new self([...$a->items, ...$b->items]);
    }

    /**
     * @param T[] $items
     * @return DocumentCollection<T>
     */
    public static function new(array $items): self
    {
        return new self($items);
    }

    /**
     * @return array<string, T[]>
     */
    public function classify(string $field): array
    {
        return ArrayUtil::classify(
            $this->items,
            static fn (Document $item) => $item->toArray(ContextService::dummy())[$field] ?? 'null'
        );
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @param callable(T): bool $callback
     * @return DocumentCollection<T>
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback));
    }

    /**
     * @return T|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T
     */
    public function get(int $key): Document
    {
        return $this->items[$key];
    }

    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $val) {
            yield $key => $val;
        }
    }

    /**
     * @return T|null
     */
    public function last()
    {
        return $this->items[$this->count() - 1] ?? null;
    }

    /**
     * @return T[]
     */
    public function list(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, T>
     */
    public function mapify(string $field): array
    {
        return ArrayUtil::mapify(
            $this->items,
            static fn (Document $item) => $item->toArray(ContextService::dummy())[$field] ?? 'null'
        );
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NektriaException('DocumentCollection is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new NektriaException('DocumentCollection is read-only');
    }

    /**
     * @return T|null
     */
    public function opt(int $key): ?Document
    {
        return $this->items[$key];
    }

    /**
     * @return DocumentCollection<T>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    /**
     * @return mixed[]
     */
    public function toArray(ContextService $context): array
    {
        $list = [];

        foreach ($this as $item) {
            $list[] = $item->toArray($context);
        }

        return $list;
    }
}

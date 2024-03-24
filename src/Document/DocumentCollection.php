<?php

declare(strict_types=1);

namespace Nektria\Document;

use function count;

/**
 * @template T of Document
 */
class DocumentCollection implements Document
{
    /**
     * @template X of Document
     * @param DocumentCollection<X> $a
     * @param DocumentCollection<X> $b
     * @return DocumentCollection<X>
     */
    public static function merge(self $a, self $b): self
    {
        return new self(array_merge($a->items, $b->items));
    }

    /**
     * @param T[] $items
     */
    public function __construct(
        private readonly array $items
    ) {
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
     * @return T
     */
    public function get(int $key): Document
    {
        return $this->items[$key];
    }

    /**
     * @return T[]
     */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return T|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function last()
    {
        return $this->items[$this->count() - 1] ?? null;
    }

    /**
     * @return mixed[]
     */
    public function toArray(string $model): array
    {
        $list = [];

        foreach ($this->items() as $item) {
            $list[] = $item->toArray($model);
        }

        return $list;
    }
}

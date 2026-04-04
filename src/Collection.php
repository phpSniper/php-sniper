<?php

declare(strict_types=1);

namespace Sniper;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Lightweight collection wrapper for database result sets.
 *
 * Provides a fluent interface for manipulating arrays of data with
 * common functional operations (map, filter, pluck, etc.) while
 * implementing standard PHP interfaces for interoperability.
 *
 * @implements ArrayAccess<int|string, mixed>
 * @implements IteratorAggregate<int|string, mixed>
 */
final class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int|string, mixed> */
    private array $items;

    /**
     * @param array<int|string, mixed> $items The initial items.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /** @return array<int|string, mixed> */
    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->items, $flags);
    }

    public function first(): mixed
    {
        $key = array_key_first($this->items);

        return $key !== null ? $this->items[$key] : null;
    }

    public function last(): mixed
    {
        $key = array_key_last($this->items);

        return $key !== null ? $this->items[$key] : null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @param callable(mixed, int|string): mixed $callback
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items, array_keys($this->items)));
    }

    /**
     * @param callable(mixed, int|string): bool|null $callback
     */
    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }

        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function pluck(string $key): self
    {
        return new self(array_column($this->items, $key));
    }

    public function groupBy(string $key): self
    {
        $groups = [];
        foreach ($this->items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $groupKey = (string) ($item[$key] ?? '');
            $groups[$groupKey][] = $item;
        }

        return new self(array_map(
            fn(array $group): self => new self($group),
            $groups,
        ));
    }

    public function chunk(int $size): self
    {
        if ($size <= 0) {
            return new self([]);
        }

        return new self(array_map(
            fn(array $chunk): self => new self($chunk),
            array_chunk($this->items, $size, true),
        ));
    }

    /**
     * @param callable(mixed, mixed): mixed $callback
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * @param callable(mixed, int|string): void $callback
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * @param callable(mixed, mixed): int|string $callbackOrColumn
     */
    public function sortBy(callable|string $callbackOrColumn, string $direction = 'asc'): self
    {
        $items = $this->items;

        if (is_string($callbackOrColumn)) {
            $column = $callbackOrColumn;
            usort($items, function (mixed $a, mixed $b) use ($column, $direction): int {
                $aVal = is_array($a) ? ($a[$column] ?? null) : null;
                $bVal = is_array($b) ? ($b[$column] ?? null) : null;
                $result = $aVal <=> $bVal;

                return $direction === 'desc' ? -$result : $result;
            });
        } else {
            usort($items, $callbackOrColumn);
        }

        return new self($items);
    }

    public function unique(?string $key = null): self
    {
        if ($key === null) {
            return new self(array_values(array_unique($this->items, SORT_REGULAR)));
        }

        $seen = [];
        $result = [];
        foreach ($this->items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $value = $item[$key] ?? null;
            $serialized = serialize($value);
            if (!isset($seen[$serialized])) {
                $seen[$serialized] = true;
                $result[] = $item;
            }
        }

        return new self($result);
    }

    /**
     * @param callable(mixed): bool $callback
     */
    public function contains(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return true;
            }
        }

        return false;
    }

    /** @return Traversable<int|string, mixed> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /** @return array<int|string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}

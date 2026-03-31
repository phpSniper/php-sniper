<?php

declare(strict_types=1);

namespace Sniper\Pagination;

use JsonSerializable;
use Sniper\Collection;

/**
 * Paginated result set.
 *
 * Wraps a page of query results with pagination metadata (total count,
 * current page, last page, etc.). JSON-serializable for direct use in
 * API responses.
 *
 * @example
 * ```php
 * $paginator = $builder->table('users')->paginate(perPage: 20, currentPage: 2);
 *
 * $paginator->items();       // Collection of users on page 2
 * $paginator->total();       // Total number of users
 * $paginator->lastPage();    // Last available page number
 * $paginator->hasMorePages(); // Whether there are more pages
 * ```
 */
class Paginator implements JsonSerializable
{
    /**
     * The last page number.
     */
    private readonly int $lastPage;

    /**
     * Create a new Paginator instance.
     *
     * @param Collection<mixed> $items The items on the current page.
     * @param int $total The total number of items across all pages.
     * @param int $perPage The number of items per page.
     * @param int $currentPage The current page number (1-indexed).
     */
    public function __construct(
        private readonly Collection $items,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $currentPage,
    ) {
        $this->lastPage = max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    /**
     * Get the items on the current page.
     *
     * @return Collection<mixed>
     */
    public function items(): Collection
    {
        return $this->items;
    }

    /**
     * Get the total number of items across all pages.
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get the number of items per page.
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get the current page number.
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get the last (maximum) page number.
     */
    public function lastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * Check if there are more pages after the current one.
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /**
     * Check if this is the first page.
     */
    public function onFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    /**
     * Check if this is the last page.
     */
    public function onLastPage(): bool
    {
        return $this->currentPage >= $this->lastPage;
    }

    /**
     * Get the number of items on the current page.
     */
    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * Check if the current page has no items.
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Get the starting item number for the current page (1-indexed).
     *
     * Useful for displaying "Showing 21-40 of 100" type messages.
     */
    public function firstItem(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
     * Get the ending item number for the current page (1-indexed).
     */
    public function lastItem(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return min($this->firstItem() + $this->perPage - 1, $this->total);
    }

    /**
     * Serialize the paginator to a JSON-friendly array.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'data'         => $this->items->toArray(),
            'total'        => $this->total,
            'per_page'     => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page'    => $this->lastPage,
            'from'         => $this->firstItem(),
            'to'           => $this->lastItem(),
            'has_more'     => $this->hasMorePages(),
        ];
    }

    /**
     * Convert the paginator to a JSON string.
     *
     * @param int $flags JSON encoding flags.
     * @return string
     */
    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->jsonSerialize(), $flags);
    }
}

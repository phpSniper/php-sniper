<?php

declare(strict_types=1);

namespace Sniper\Exceptions;

/**
 * Thrown when a SQL query fails to execute.
 *
 * Includes the compiled SQL and bound parameters for debugging.
 * In production, consumers should catch this and log it — never expose
 * raw SQL to end users.
 */
class QueryException extends SniperException
{
    /**
     * Create a new QueryException from a failed query.
     *
     * @param string $sql The SQL statement that failed.
     * @param array<int|string, mixed> $bindings The parameter bindings that were used.
     * @param string $message The underlying driver error message.
     * @param int $code The underlying driver error code.
     * @param \Throwable|null $previous The original exception from PDO.
     */
    public function __construct(
        private readonly string $sql,
        private readonly array $bindings = [],
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $contextMessage = sprintf(
            "Query failed: %s\n\nSQL: %s\n\nBindings: %s",
            $message,
            $this->sql,
            json_encode($this->bindings, JSON_PRETTY_PRINT),
        );

        parent::__construct($contextMessage, $code, $previous);
    }

    /**
     * Get the SQL query that failed.
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Get the parameter bindings that were used.
     *
     * @return array<int|string, mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}

<?php

declare(strict_types=1);

namespace Sniper\Exceptions;

/**
 * Thrown when a database connection cannot be established.
 *
 * Wraps the underlying PDO connection error with additional context
 * such as the host and database name that failed.
 */
class ConnectionException extends SniperException
{
    /**
     * Create a new ConnectionException from a failed connection attempt.
     *
     * @param string $host The database host that was targeted.
     * @param string $database The database name that was targeted.
     * @param string $message The underlying driver error message.
     * @param int $code The underlying driver error code.
     * @param \Throwable|null $previous The original exception from PDO.
     */
    public function __construct(
        private readonly string $host,
        private readonly string $database,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $contextMessage = sprintf(
            'Failed to connect to database "%s" on host "%s": %s',
            $this->database,
            $this->host,
            $message,
        );

        parent::__construct($contextMessage, $code, $previous);
    }

    /**
     * Get the host that the connection failed on.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the database name that the connection failed on.
     */
    public function getDatabase(): string
    {
        return $this->database;
    }
}

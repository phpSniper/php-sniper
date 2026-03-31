<?php

declare(strict_types=1);

namespace Sniper;

/**
 * Transaction helper.
 *
 * Provides a clean functional interface for running database operations
 * inside transactions with automatic commit/rollback semantics.
 *
 * This class is a convenience wrapper around Connection's transaction methods.
 * For most use cases, prefer `Connection::transaction()` or `Sniper::transaction()`.
 *
 * @example
 * ```php
 * $transaction = new Transaction($connection);
 *
 * $result = $transaction->run(function () use ($connection) {
 *     $connection->insert('INSERT INTO accounts ...', [...]);
 *     $connection->affectingStatement('UPDATE accounts ...', [...]);
 *     return 'Transfer complete';
 * });
 * ```
 */
class Transaction
{
    /**
     * Create a new Transaction instance.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Run a callable inside a database transaction.
     *
     * If the callable throws an exception, the transaction is automatically
     * rolled back and the exception is re-thrown. If it succeeds, the
     * transaction is committed and the return value is passed through.
     *
     * Supports nested transactions via savepoints.
     *
     * @template T
     * @param callable(): T $callback The operation to run.
     * @return T The return value of the callback.
     * @throws \Throwable Re-throws after rollback.
     */
    public function run(callable $callback): mixed
    {
        return $this->connection->transaction($callback);
    }

    /**
     * Manually begin a transaction.
     *
     * Prefer `run()` for automatic commit/rollback. Use manual methods
     * only when you need fine-grained control.
     */
    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Manually commit the current transaction.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Manually roll back the current transaction.
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Get the current transaction nesting depth.
     */
    public function depth(): int
    {
        return $this->connection->getTransactionDepth();
    }
}

<?php

declare(strict_types=1);

namespace Sniper;

use PDO;
use PDOException;
use Sniper\Exceptions\ConnectionException;
use Sniper\Exceptions\QueryException;

/**
 * PDO connection manager.
 *
 * Manages a single PDO instance with lazy initialization and secure defaults.
 * Provides methods for executing raw queries with prepared statements and
 * managing transactions.
 *
 * @example
 * ```php
 * $connection = Connection::create([
 *     'driver'   => 'mysql',
 *     'host'     => 'localhost',
 *     'database' => 'myapp',
 *     'username' => 'root',
 *     'password' => 'secret',
 * ]);
 *
 * // Execute a prepared statement
 * $users = $connection->select('SELECT * FROM users WHERE active = ?', [1]);
 * ```
 */
final class Connection
{
    /** @var PDO|null The underlying PDO instance (lazy-initialized). */
    private ?PDO $pdo = null;

    /** @var int Tracks nested transaction depth for savepoint support. */
    private int $transactionDepth = 0;

    /**
     * Secure PDO connection options.
     *
     * - ERRMODE_EXCEPTION: Throw exceptions instead of silent failures.
     * - EMULATE_PREPARES=false: Use native database-level prepared statements.
     * - FETCH_ASSOC: Return associative arrays by default.
     * - STRINGIFY_FETCHES=false: Preserve native PHP types for integers, floats, etc.
     *
     * @var array<int, mixed>
     */
    private const SECURE_DEFAULTS = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    /**
     * Create a new Connection instance.
     *
     * @param Config $config The database configuration.
     */
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * Factory: create a Connection from a raw configuration array.
     *
     * @param array<string, mixed> $config Database configuration.
     * @return static
     */
    public static function create(array $config): static
    {
        return new self(new Config($config));
    }

    /**
     * Get the underlying PDO instance, creating it if necessary.
     *
     * @return PDO
     * @throws ConnectionException If the connection fails.
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createPdo();
        }

        return $this->pdo;
    }

    /**
     * Execute a SELECT query and return all matching rows.
     *
     * @param string $sql The SQL query with parameter placeholders.
     * @param array<int|string, mixed> $bindings The values to bind to placeholders.
     * @return array<int, array<string, mixed>> The result rows as associative arrays.
     * @throws QueryException If the query fails.
     */
    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->execute($sql, $bindings);

        /** @var array<int, array<string, mixed>> */
        return $statement->fetchAll();
    }

    /**
     * Execute a SELECT query and return only the first row.
     *
     * @param string $sql The SQL query with parameter placeholders.
     * @param array<int|string, mixed> $bindings The values to bind to placeholders.
     * @return array<string, mixed>|null The first row, or null if no results.
     * @throws QueryException If the query fails.
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = $this->execute($sql, $bindings);
        $row = $statement->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Execute an INSERT statement and return the last inserted ID.
     *
     * @param string $sql The INSERT SQL with parameter placeholders.
     * @param array<int|string, mixed> $bindings The values to bind.
     * @return string The last inserted auto-increment ID.
     * @throws QueryException If the query fails.
     */
    public function insert(string $sql, array $bindings = []): string
    {
        $this->execute($sql, $bindings);

        $lastId = $this->getPdo()->lastInsertId();

        return $lastId !== false ? $lastId : '0';
    }

    /**
     * Execute an UPDATE or DELETE statement and return the number of affected rows.
     *
     * @param string $sql The SQL statement with parameter placeholders.
     * @param array<int|string, mixed> $bindings The values to bind.
     * @return int The number of rows affected.
     * @throws QueryException If the query fails.
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        $statement = $this->execute($sql, $bindings);

        return $statement->rowCount();
    }

    /**
     * Execute a raw SQL statement (for DDL, etc.).
     *
     * @param string $sql The SQL statement.
     * @param array<int|string, mixed> $bindings Optional bindings.
     * @return bool True on success.
     * @throws QueryException If the query fails.
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        $this->execute($sql, $bindings);

        return true;
    }

    /**
     * Begin a database transaction, supporting nested transactions via savepoints.
     *
     * @throws QueryException If the transaction cannot be started.
     */
    public function beginTransaction(): void
    {
        if ($this->transactionDepth === 0) {
            $this->getPdo()->beginTransaction();
        } else {
            $this->execute("SAVEPOINT sniper_savepoint_{$this->transactionDepth}");
        }

        $this->transactionDepth++;
    }

    /**
     * Commit the current transaction (or release a savepoint for nested transactions).
     *
     * @throws QueryException If the commit fails.
     */
    public function commit(): void
    {
        $this->transactionDepth = max(0, $this->transactionDepth - 1);

        if ($this->transactionDepth === 0) {
            $this->getPdo()->commit();
        } else {
            $this->execute("RELEASE SAVEPOINT sniper_savepoint_{$this->transactionDepth}");
        }
    }

    /**
     * Roll back the current transaction (or to a savepoint for nested transactions).
     *
     * @throws QueryException If the rollback fails.
     */
    public function rollBack(): void
    {
        $this->transactionDepth = max(0, $this->transactionDepth - 1);

        if ($this->transactionDepth === 0) {
            $this->getPdo()->rollBack();
        } else {
            $this->execute("ROLLBACK TO SAVEPOINT sniper_savepoint_{$this->transactionDepth}");
        }
    }

    /**
     * Execute a callable within a transaction, with automatic commit/rollback.
     *
     * @template T
     * @param callable(): T $callback The operation to run inside the transaction.
     * @return T The return value of the callback.
     * @throws \Throwable Re-throws the exception after rolling back.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback();
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Get the current transaction nesting depth.
     */
    public function getTransactionDepth(): int
    {
        return $this->transactionDepth;
    }

    /**
     * Get the configuration object.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Disconnect from the database (useful for long-running processes).
     */
    public function disconnect(): void
    {
        $this->pdo = null;
        $this->transactionDepth = 0;
    }

    /**
     * Reconnect to the database.
     *
     * @throws ConnectionException If reconnection fails.
     */
    public function reconnect(): void
    {
        $this->disconnect();
        $this->pdo = $this->createPdo();
    }

    /**
     * Execute a prepared statement with parameter bindings.
     *
     * This is the single point where ALL queries are executed — ensuring
     * that every query goes through PDO prepared statements.
     *
     * @param string $sql The SQL with parameter placeholders.
     * @param array<int|string, mixed> $bindings The values to bind.
     * @return \PDOStatement The executed statement.
     * @throws QueryException If the query fails.
     */
    private function execute(string $sql, array $bindings = []): \PDOStatement
    {
        try {
            $statement = $this->getPdo()->prepare($sql);
            $statement->execute($bindings);

            return $statement;
        } catch (PDOException $e) {
            throw new QueryException(
                sql: $sql,
                bindings: $bindings,
                message: $e->getMessage(),
                code: (int) $e->getCode(),
                previous: $e,
            );
        }
    }

    /**
     * Create a new PDO instance with secure defaults.
     *
     * @return PDO
     * @throws ConnectionException If the connection fails.
     */
    private function createPdo(): PDO
    {
        $dsn = $this->config->getDsn();
        $username = $this->config->get('username');
        $password = $this->config->get('password');

        // Merge user-provided PDO options with our secure defaults
        /** @var array<int, mixed> $userOptions */
        $userOptions = $this->config->get('options') ?? [];
        $options = $userOptions + self::SECURE_DEFAULTS;

        try {
            return new PDO(
                $dsn,
                is_string($username) ? $username : null,
                is_string($password) ? $password : null,
                $options,
            );
        } catch (PDOException $e) {
            /** @var string $host */
            $host = $this->config->get('host') ?? 'unknown';
            /** @var string $database */
            $database = $this->config->get('database') ?? 'unknown';

            throw new ConnectionException(
                host: $host,
                database: $database,
                message: $e->getMessage(),
                code: (int) $e->getCode(),
                previous: $e,
            );
        }
    }
}

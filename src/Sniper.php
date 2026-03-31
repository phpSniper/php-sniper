<?php

declare(strict_types=1);

namespace Sniper;

use Sniper\Exceptions\SniperException;
use Sniper\Grammar\Grammar;
use Sniper\Grammar\MySqlGrammar;
use Sniper\Grammar\SqliteGrammar;

/**
 * phpSniper — Main entry point and static facade.
 *
 * Provides a simple, one-liner API for database operations. Initialize once
 * with `Sniper::connect()`, then use `Sniper::table()` to start building queries.
 *
 * @example
 * ```php
 * use Sniper\Sniper;
 *
 * // 1. Initialize (once, at application bootstrap)
 * Sniper::connect([
 *     'driver'   => 'mysql',
 *     'host'     => 'localhost',
 *     'database' => 'myapp',
 *     'username' => 'root',
 *     'password' => 'secret',
 * ]);
 *
 * // 2. Query
 * $users = Sniper::table('users')
 *     ->where('active', true)
 *     ->orderBy('name')
 *     ->get();
 *
 * // 3. Insert
 * $id = Sniper::table('users')->insert([
 *     'name'  => 'Jane Doe',
 *     'email' => 'jane@example.com',
 * ]);
 *
 * // 4. Transaction
 * Sniper::transaction(function () {
 *     Sniper::table('accounts')->where('id', 1)->update(['balance' => 900]);
 *     Sniper::table('accounts')->where('id', 2)->update(['balance' => 1100]);
 * });
 * ```
 */
class Sniper
{
    /**
     * The shared connection instance.
     */
    private static ?Connection $connection = null;

    /**
     * The SQL grammar instance.
     */
    private static ?Grammar $grammar = null;

    /**
     * The configuration instance.
     */
    private static ?Config $config = null;

    /**
     * Initialize phpSniper with database credentials.
     *
     * Call this once at the top of your application. All subsequent
     * `Sniper::table()` calls will use this connection.
     *
     * @param array<string, mixed> $config Database configuration array.
     *   Supported keys: driver, host, port, database, username, password, charset, prefix, options
     * @return void
     */
    public static function connect(array $config): void
    {
        self::$config = new Config($config);
        self::$connection = new Connection(self::$config);
        self::$grammar = self::resolveGrammar(self::$config);
    }

    /**
     * Initialize phpSniper from environment variables.
     *
     * Reads `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, etc.
     *
     * @param string|null $envFilePath Optional path to a `.env` file.
     * @return void
     */
    public static function connectFromEnv(?string $envFilePath = null): void
    {
        self::$config = Config::fromEnvironment($envFilePath);
        self::$connection = new Connection(self::$config);
        self::$grammar = self::resolveGrammar(self::$config);
    }

    /**
     * Start a new query on a table.
     *
     * Returns a fresh `QueryBuilder` instance bound to the given table.
     *
     * @param string $table The table name (supports aliases: "users AS u").
     * @return QueryBuilder A new query builder for the table.
     * @throws SniperException If not connected.
     */
    public static function table(string $table): QueryBuilder
    {
        return self::newBuilder()->table($table);
    }

    /**
     * Execute a raw SQL query with prepared statement bindings.
     *
     * Use this for complex queries that the builder cannot express.
     * Always use `?` placeholders and pass values as bindings.
     *
     * @param string $sql The SQL query with `?` placeholders.
     * @param array<int|string, mixed> $bindings The parameter values.
     * @return array<int, array<string, mixed>> The result rows.
     * @throws SniperException If not connected.
     */
    public static function raw(string $sql, array $bindings = []): array
    {
        return self::getConnection()->select($sql, $bindings);
    }

    /**
     * Execute a raw SQL statement (INSERT, UPDATE, DELETE, DDL).
     *
     * @param string $sql The SQL statement with `?` placeholders.
     * @param array<int|string, mixed> $bindings The parameter values.
     * @return bool True on success.
     * @throws SniperException If not connected.
     */
    public static function statement(string $sql, array $bindings = []): bool
    {
        return self::getConnection()->statement($sql, $bindings);
    }

    /**
     * Run a callable inside a database transaction.
     *
     * The transaction is automatically committed on success or rolled back
     * if the callable throws an exception.
     *
     * @template T
     * @param callable(): T $callback The operation to run.
     * @return T The value returned by the callback.
     * @throws \Throwable Re-throws after rollback.
     */
    public static function transaction(callable $callback): mixed
    {
        return self::getConnection()->transaction($callback);
    }

    /**
     * Get the underlying Connection instance.
     *
     * @return Connection
     * @throws SniperException If not connected.
     */
    public static function getConnection(): Connection
    {
        if (self::$connection === null) {
            throw new SniperException(
                'phpSniper is not connected. Call Sniper::connect() or Sniper::connectFromEnv() first.',
            );
        }

        return self::$connection;
    }

    /**
     * Get the underlying PDO instance.
     *
     * Useful for advanced operations that require direct PDO access.
     *
     * @return \PDO
     * @throws SniperException If not connected.
     */
    public static function getPdo(): \PDO
    {
        return self::getConnection()->getPdo();
    }

    /**
     * Get the current Grammar instance.
     *
     * @return Grammar
     * @throws SniperException If not connected.
     */
    public static function getGrammar(): Grammar
    {
        if (self::$grammar === null) {
            throw new SniperException(
                'phpSniper is not connected. Call Sniper::connect() or Sniper::connectFromEnv() first.',
            );
        }

        return self::$grammar;
    }

    /**
     * Disconnect from the database and reset the singleton state.
     *
     * Useful for testing or long-running processes that need to reset.
     */
    public static function disconnect(): void
    {
        self::$connection?->disconnect();
        self::$connection = null;
        self::$grammar = null;
        self::$config = null;
    }

    /**
     * Check if phpSniper is currently connected.
     */
    public static function isConnected(): bool
    {
        return self::$connection !== null;
    }

    /**
     * Create a new QueryBuilder instance.
     *
     * @return QueryBuilder
     * @throws SniperException If not connected.
     */
    private static function newBuilder(): QueryBuilder
    {
        return new QueryBuilder(
            self::getConnection(),
            self::getGrammar(),
        );
    }

    /**
     * Resolve the correct grammar for the configured database driver.
     *
     * @param Config $config The database configuration.
     * @return Grammar The driver-specific grammar instance.
     */
    private static function resolveGrammar(Config $config): Grammar
    {
        $grammar = match ($config->getDriver()) {
            'mysql'  => new MySqlGrammar(),
            'sqlite' => new SqliteGrammar(),
            default  => new Grammar(),
        };

        $grammar->setTablePrefix($config->getPrefix());

        return $grammar;
    }
}

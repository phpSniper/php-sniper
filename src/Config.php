<?php

declare(strict_types=1);

namespace Sniper;

use Sniper\Exceptions\SniperException;

/**
 * Database configuration handler.
 *
 * Loads, validates, and provides access to database connection parameters
 * from an explicit array, environment variables, or a `.env` file.
 *
 * @example
 * ```php
 * // From an explicit array
 * $config = new Config([
 *     'driver'   => 'mysql',
 *     'host'     => 'localhost',
 *     'database' => 'myapp',
 *     'username' => 'root',
 *     'password' => 'secret',
 * ]);
 *
 * // From environment variables (DB_HOST, DB_DATABASE, etc.)
 * $config = Config::fromEnvironment();
 * ```
 */
final class Config
{
    /** @var array<string, mixed> The resolved configuration values. */
    private readonly array $resolved;

    /**
     * Default configuration values.
     *
     * @var array<string, mixed>
     */
    private const DEFAULTS = [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => null,
        'database' => '',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'prefix'   => '',
        'options'  => [],
    ];

    /**
     * Map of environment variable names to config keys.
     *
     * @var array<string, string>
     */
    private const ENV_MAP = [
        'DB_DRIVER'   => 'driver',
        'DB_HOST'     => 'host',
        'DB_PORT'     => 'port',
        'DB_DATABASE' => 'database',
        'DB_USERNAME' => 'username',
        'DB_PASSWORD' => 'password',
        'DB_CHARSET'  => 'charset',
        'DB_PREFIX'   => 'prefix',
    ];

    /**
     * Supported database drivers.
     *
     * @var list<string>
     */
    private const SUPPORTED_DRIVERS = ['mysql', 'sqlite', 'pgsql'];

    /**
     * Create a new Config instance.
     *
     * @param array<string, mixed> $config Key-value pairs of configuration.
     * @throws SniperException If required keys are missing or invalid.
     */
    public function __construct(array $config)
    {
        $this->resolved = $this->validate(
            array_merge(self::DEFAULTS, $config),
        );
    }

    /**
     * Create a Config instance from environment variables.
     *
     * Reads standard `DB_*` environment variables (e.g., `DB_HOST`, `DB_DATABASE`).
     * Supports optional `.env` file loading via a path parameter.
     *
     * @param string|null $envFilePath Optional path to a `.env` file to load first.
     * @return static
     */
    public static function fromEnvironment(?string $envFilePath = null): static
    {
        if ($envFilePath !== null) {
            self::loadEnvFile($envFilePath);
        }

        $config = [];
        foreach (self::ENV_MAP as $envKey => $configKey) {
            $value = getenv($envKey);
            if ($value !== false) {
                $config[$configKey] = $value;
            }
        }

        return new self($config);
    }

    /**
     * Build the PDO DSN string from the resolved configuration.
     *
     * @return string The PDO-compatible Data Source Name.
     */
    public function getDsn(): string
    {
        $driver = $this->get('driver');

        if ($driver === 'sqlite') {
            return 'sqlite:' . $this->get('database');
        }

        $dsn = $driver . ':host=' . $this->get('host')
            . ';dbname=' . $this->get('database')
            . ';charset=' . $this->get('charset');

        $port = $this->get('port');
        if ($port !== null) {
            $dsn .= ';port=' . $port;
        }

        return $dsn;
    }

    /**
     * Get a single configuration value.
     *
     * @param string $key The configuration key.
     * @return mixed The configuration value, or null if not set.
     */
    public function get(string $key): mixed
    {
        return $this->resolved[$key] ?? null;
    }

    /**
     * Get the driver name.
     */
    public function getDriver(): string
    {
        /** @var string */
        return $this->resolved['driver'];
    }

    /**
     * Get the table prefix.
     */
    public function getPrefix(): string
    {
        /** @var string */
        return $this->resolved['prefix'];
    }

    /**
     * Get the full resolved configuration array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->resolved;
    }

    /**
     * Validate the configuration and enforce required fields.
     *
     * @param array<string, mixed> $config The merged configuration.
     * @return array<string, mixed> The validated configuration.
     * @throws SniperException If validation fails.
     */
    private function validate(array $config): array
    {
        $driver = $config['driver'] ?? '';

        if (!in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new SniperException(sprintf(
                'Unsupported database driver "%s". Supported drivers: %s',
                $driver,
                implode(', ', self::SUPPORTED_DRIVERS),
            ));
        }

        // SQLite only requires a database path (file path or :memory:)
        if ($driver === 'sqlite') {
            if (empty($config['database'])) {
                throw new SniperException('SQLite requires a "database" path (file path or ":memory:").');
            }

            return $config;
        }

        // MySQL / PostgreSQL require host and database
        if (empty($config['database'])) {
            throw new SniperException(sprintf(
                'The "%s" driver requires a "database" name.',
                $driver,
            ));
        }

        return $config;
    }

    /**
     * Load a `.env` file and set its values as environment variables.
     *
     * This is a simple, dependency-free `.env` parser. For production projects,
     * consider using `vlucas/phpdotenv` instead.
     *
     * @param string $path Absolute path to the `.env` file.
     * @throws SniperException If the file cannot be read.
     */
    private static function loadEnvFile(string $path): void
    {
        if (!is_readable($path)) {
            throw new SniperException(sprintf('Cannot read .env file at "%s".', $path));
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new SniperException(sprintf('Failed to parse .env file at "%s".', $path));
        }

        foreach ($lines as $line) {
            // Skip comments
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Parse KEY=VALUE pairs
            $equalsPosition = strpos($line, '=');
            if ($equalsPosition === false) {
                continue;
            }

            $key = trim(substr($line, 0, $equalsPosition));
            $value = trim(substr($line, $equalsPosition + 1));

            // Strip surrounding quotes from value
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv("$key=$value");
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sniper\Config;
use Sniper\Exceptions\SniperException;

/**
 * Unit tests for the Config class.
 *
 * Verifies configuration loading, validation, DSN building, and
 * environment variable handling.
 */
final class ConfigTest extends TestCase
{
    public function testCreatesConfigFromArray(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => 'testdb',
            'username' => 'root',
            'password' => 'secret',
        ]);

        $this->assertSame('mysql', $config->getDriver());
        $this->assertSame('localhost', $config->get('host'));
        $this->assertSame('testdb', $config->get('database'));
        $this->assertSame('root', $config->get('username'));
        $this->assertSame('secret', $config->get('password'));
    }

    public function testAppliesDefaultValues(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'database' => 'testdb',
        ]);

        $this->assertSame('127.0.0.1', $config->get('host'));
        $this->assertSame('root', $config->get('username'));
        $this->assertSame('', $config->get('password'));
        $this->assertSame('utf8mb4', $config->get('charset'));
        $this->assertSame('', $config->getPrefix());
    }

    public function testBuildsMysqlDsn(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'host'     => 'db.example.com',
            'database' => 'myapp',
            'port'     => 3307,
            'charset'  => 'utf8mb4',
        ]);

        $dsn = $config->getDsn();

        $this->assertSame('mysql:host=db.example.com;dbname=myapp;charset=utf8mb4;port=3307', $dsn);
    }

    public function testBuildsMysqlDsnWithoutPort(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => 'myapp',
        ]);

        $dsn = $config->getDsn();

        $this->assertSame('mysql:host=localhost;dbname=myapp;charset=utf8mb4', $dsn);
    }

    public function testBuildsSqliteDsn(): void
    {
        $config = new Config([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->assertSame('sqlite::memory:', $config->getDsn());
    }

    public function testBuildsSqliteDsnWithFilePath(): void
    {
        $config = new Config([
            'driver'   => 'sqlite',
            'database' => '/tmp/test.db',
        ]);

        $this->assertSame('sqlite:/tmp/test.db', $config->getDsn());
    }

    public function testThrowsOnUnsupportedDriver(): void
    {
        $this->expectException(SniperException::class);
        $this->expectExceptionMessage('Unsupported database driver');

        new Config(['driver' => 'oracle']);
    }

    public function testThrowsOnMissingDatabaseForMysql(): void
    {
        $this->expectException(SniperException::class);
        $this->expectExceptionMessage('requires a "database" name');

        new Config(['driver' => 'mysql', 'database' => '']);
    }

    public function testThrowsOnMissingDatabaseForSqlite(): void
    {
        $this->expectException(SniperException::class);
        $this->expectExceptionMessage('SQLite requires a "database" path');

        new Config(['driver' => 'sqlite', 'database' => '']);
    }

    public function testToArrayReturnsFullConfig(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'database' => 'testdb',
        ]);

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('driver', $array);
        $this->assertArrayHasKey('host', $array);
        $this->assertArrayHasKey('database', $array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('charset', $array);
        $this->assertArrayHasKey('prefix', $array);
    }

    public function testGetPrefixReturnsEmptyStringByDefault(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'database' => 'testdb',
        ]);

        $this->assertSame('', $config->getPrefix());
    }

    public function testGetPrefixReturnsConfiguredPrefix(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'database' => 'testdb',
            'prefix'   => 'wp_',
        ]);

        $this->assertSame('wp_', $config->getPrefix());
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $config = new Config([
            'driver'   => 'mysql',
            'database' => 'testdb',
        ]);

        $this->assertNull($config->get('nonexistent'));
    }

    public function testFromEnvironment(): void
    {
        // Set environment variables
        putenv('DB_DRIVER=sqlite');
        putenv('DB_DATABASE=:memory:');

        $config = Config::fromEnvironment();

        $this->assertSame('sqlite', $config->getDriver());
        $this->assertSame(':memory:', $config->get('database'));

        // Cleanup
        putenv('DB_DRIVER');
        putenv('DB_DATABASE');
    }
}

<?php

declare(strict_types=1);

namespace Sniper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sniper\Connection;
use Sniper\Exceptions\ConnectionException;

final class ConnectionTest extends TestCase
{
    public function testCreatesSqliteConnection(): void
    {
        $conn = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->assertInstanceOf(\PDO::class, $conn->getPdo());
    }

    public function testExecutesSelect(): void
    {
        $conn = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $result = $conn->select('SELECT 1 + 1 AS total');
        $this->assertSame(2, $result[0]['total']);
    }

    public function testSelectOneReturnsNullForNoResults(): void
    {
        $conn = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $conn->statement('CREATE TABLE test (id INTEGER)');
        $this->assertNull($conn->selectOne('SELECT * FROM test WHERE id = ?', [999]));
    }

    public function testInsertsAndReturnsId(): void
    {
        $conn = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $conn->statement('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->assertSame('1', $conn->insert('INSERT INTO test (name) VALUES (?)', ['Alice']));
    }

    public function testAffectingStatement(): void
    {
        $conn = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $conn->statement('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $conn->insert('INSERT INTO test (name) VALUES (?)', ['Alice']);
        $this->assertSame(1, $conn->affectingStatement('DELETE FROM test WHERE name = ?', ['Alice']));
    }

    public function testThrowsConnectionExceptionOnBadHost(): void
    {
        $this->expectException(ConnectionException::class);
        $conn = Connection::create([
            'driver' => 'mysql', 'host' => 'nonexistent.invalid',
            'database' => 'testdb', 'username' => 'nobody', 'password' => 'no',
        ]);
        $conn->getPdo();
    }
}

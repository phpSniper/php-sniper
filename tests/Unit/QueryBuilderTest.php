<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sniper\Collection;
use Sniper\Connection;
use Sniper\Grammar\SqliteGrammar;
use Sniper\QueryBuilder;

/**
 * Unit tests for the QueryBuilder.
 *
 * Uses SQLite in-memory to verify that the builder produces correct SQL
 * and returns expected results.
 */
final class QueryBuilderTest extends TestCase
{
    private Connection $connection;
    private QueryBuilder $builder;

    protected function setUp(): void
    {
        $this->connection = Connection::create([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        // Create a test table
        $this->connection->statement('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                age INTEGER DEFAULT 0,
                active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Seed test data
        $this->connection->insert(
            'INSERT INTO users (name, email, age, active) VALUES (?, ?, ?, ?)',
            ['Alice', 'alice@test.com', 25, 1],
        );
        $this->connection->insert(
            'INSERT INTO users (name, email, age, active) VALUES (?, ?, ?, ?)',
            ['Bob', 'bob@test.com', 30, 1],
        );
        $this->connection->insert(
            'INSERT INTO users (name, email, age, active) VALUES (?, ?, ?, ?)',
            ['Charlie', 'charlie@test.com', 35, 0],
        );

        $this->builder = new QueryBuilder($this->connection, new SqliteGrammar());
    }

    // ---------------------------------------------------------------
    // SELECT
    // ---------------------------------------------------------------

    public function testSelectAll(): void
    {
        $result = $this->builder->table('users')->get();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(3, $result->count());
    }

    public function testSelectColumns(): void
    {
        $result = $this->builder->table('users')->select('name', 'email')->first();

        $this->assertNotNull($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayNotHasKey('age', $result);
    }

    public function testSelectFirst(): void
    {
        $result = $this->builder->table('users')->orderBy('name')->first();

        $this->assertNotNull($result);
        $this->assertSame('Alice', $result['name']);
    }

    public function testSelectValue(): void
    {
        $email = $this->builder->table('users')->where('name', 'Alice')->value('email');

        $this->assertSame('alice@test.com', $email);
    }

    public function testSelectPluck(): void
    {
        $names = $this->builder->table('users')->orderBy('name')->pluck('name');

        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names->toArray());
    }

    public function testSelectDistinct(): void
    {
        $result = $this->builder->table('users')->select('active')->distinct()->get();

        $this->assertSame(2, $result->count());
    }

    // ---------------------------------------------------------------
    // WHERE
    // ---------------------------------------------------------------

    public function testWhereEquals(): void
    {
        $result = $this->builder->table('users')->where('name', 'Alice')->get();

        $this->assertSame(1, $result->count());
        $this->assertSame('Alice', $result->first()['name']);
    }

    public function testWhereWithOperator(): void
    {
        $result = $this->builder->table('users')->where('age', '>=', 30)->get();

        $this->assertSame(2, $result->count());
    }

    public function testOrWhere(): void
    {
        $result = $this->builder->table('users')
            ->where('name', 'Alice')
            ->orWhere('name', 'Bob')
            ->get();

        $this->assertSame(2, $result->count());
    }

    public function testWhereIn(): void
    {
        $result = $this->builder->table('users')
            ->whereIn('name', ['Alice', 'Charlie'])
            ->get();

        $this->assertSame(2, $result->count());
    }

    public function testWhereNotIn(): void
    {
        $result = $this->builder->table('users')
            ->whereNotIn('name', ['Alice', 'Charlie'])
            ->get();

        $this->assertSame(1, $result->count());
        $this->assertSame('Bob', $result->first()['name']);
    }

    public function testWhereBetween(): void
    {
        $result = $this->builder->table('users')
            ->whereBetween('age', 25, 32)
            ->get();

        $this->assertSame(2, $result->count());
    }

    public function testWhereRaw(): void
    {
        $result = $this->builder->table('users')
            ->whereRaw('"age" > ? AND "active" = ?', [28, 1])
            ->get();

        $this->assertSame(1, $result->count());
        $this->assertSame('Bob', $result->first()['name']);
    }

    // ---------------------------------------------------------------
    // ORDER BY, LIMIT, OFFSET
    // ---------------------------------------------------------------

    public function testOrderBy(): void
    {
        $result = $this->builder->table('users')->orderBy('age', 'desc')->first();

        $this->assertSame('Charlie', $result['name']);
    }

    public function testLatest(): void
    {
        $result = $this->builder->table('users')->latest('age')->first();

        $this->assertSame('Charlie', $result['name']);
    }

    public function testOldest(): void
    {
        $result = $this->builder->table('users')->oldest('age')->first();

        $this->assertSame('Alice', $result['name']);
    }

    public function testLimitAndOffset(): void
    {
        $result = $this->builder->table('users')
            ->orderBy('name')
            ->limit(1)
            ->offset(1)
            ->get();

        $this->assertSame(1, $result->count());
        $this->assertSame('Bob', $result->first()['name']);
    }

    public function testTakeAndSkip(): void
    {
        $result = $this->builder->table('users')
            ->orderBy('name')
            ->take(2)
            ->skip(1)
            ->get();

        $this->assertSame(2, $result->count());
    }

    // ---------------------------------------------------------------
    // EXISTS
    // ---------------------------------------------------------------

    public function testExists(): void
    {
        $this->assertTrue(
            $this->builder->table('users')->where('name', 'Alice')->exists(),
        );
    }

    public function testDoesntExist(): void
    {
        $this->assertTrue(
            $this->builder->table('users')->where('name', 'Nonexistent')->doesntExist(),
        );
    }

    // ---------------------------------------------------------------
    // AGGREGATES
    // ---------------------------------------------------------------

    public function testCount(): void
    {
        $count = $this->builder->table('users')->count();

        $this->assertSame(3, $count);
    }

    public function testCountWithCondition(): void
    {
        $count = $this->builder->table('users')->where('active', 1)->count();

        $this->assertSame(2, $count);
    }

    public function testSum(): void
    {
        $sum = $this->builder->table('users')->sum('age');

        $this->assertEquals(90, $sum);
    }

    public function testAvg(): void
    {
        $avg = $this->builder->table('users')->avg('age');

        $this->assertEquals(30.0, $avg);
    }

    public function testMin(): void
    {
        $min = $this->builder->table('users')->min('age');

        $this->assertEquals(25, $min);
    }

    public function testMax(): void
    {
        $max = $this->builder->table('users')->max('age');

        $this->assertEquals(35, $max);
    }

    // ---------------------------------------------------------------
    // INSERT
    // ---------------------------------------------------------------

    public function testInsert(): void
    {
        $id = $this->builder->table('users')->insert([
            'name'  => 'Diana',
            'email' => 'diana@test.com',
            'age'   => 28,
        ]);

        $this->assertSame('4', $id);

        // Verify the row exists
        $row = (new QueryBuilder($this->connection, new SqliteGrammar()))
            ->table('users')
            ->where('name', 'Diana')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('diana@test.com', $row['email']);
    }

    public function testInsertBatch(): void
    {
        $result = $this->builder->table('users')->insertBatch([
            ['name' => 'Eve', 'email' => 'eve@test.com', 'age' => 22],
            ['name' => 'Frank', 'email' => 'frank@test.com', 'age' => 40],
        ]);

        $this->assertTrue($result);

        $count = (new QueryBuilder($this->connection, new SqliteGrammar()))
            ->table('users')
            ->count();

        $this->assertSame(5, $count);
    }

    // ---------------------------------------------------------------
    // UPDATE
    // ---------------------------------------------------------------

    public function testUpdate(): void
    {
        $affected = $this->builder->table('users')
            ->where('name', 'Alice')
            ->update(['email' => 'newalice@test.com']);

        $this->assertSame(1, $affected);

        $row = (new QueryBuilder($this->connection, new SqliteGrammar()))
            ->table('users')
            ->where('name', 'Alice')
            ->first();

        $this->assertSame('newalice@test.com', $row['email']);
    }

    // ---------------------------------------------------------------
    // DELETE
    // ---------------------------------------------------------------

    public function testDelete(): void
    {
        $affected = $this->builder->table('users')
            ->where('name', 'Charlie')
            ->delete();

        $this->assertSame(1, $affected);

        $count = (new QueryBuilder($this->connection, new SqliteGrammar()))
            ->table('users')
            ->count();

        $this->assertSame(2, $count);
    }

    // ---------------------------------------------------------------
    // PAGINATION
    // ---------------------------------------------------------------

    public function testPaginate(): void
    {
        $paginator = $this->builder->table('users')
            ->orderBy('name')
            ->paginate(perPage: 2, currentPage: 1);

        $this->assertSame(3, $paginator->total());
        $this->assertSame(2, $paginator->perPage());
        $this->assertSame(1, $paginator->currentPage());
        $this->assertSame(2, $paginator->lastPage());
        $this->assertTrue($paginator->hasMorePages());
        $this->assertTrue($paginator->onFirstPage());
        $this->assertFalse($paginator->onLastPage());
        $this->assertSame(2, $paginator->count());
    }

    public function testPaginateLastPage(): void
    {
        $paginator = $this->builder->table('users')
            ->orderBy('name')
            ->paginate(perPage: 2, currentPage: 2);

        $this->assertFalse($paginator->hasMorePages());
        $this->assertTrue($paginator->onLastPage());
        $this->assertSame(1, $paginator->count());
    }

    // ---------------------------------------------------------------
    // DEBUGGING
    // ---------------------------------------------------------------

    public function testToSql(): void
    {
        $compiled = $this->builder->table('users')
            ->where('active', 1)
            ->orderBy('name')
            ->toSql();

        $this->assertStringContainsString('SELECT', $compiled['sql']);
        $this->assertStringContainsString('WHERE', $compiled['sql']);
        $this->assertSame([1], $compiled['bindings']);
    }

    public function testToRawSql(): void
    {
        $rawSql = $this->builder->table('users')
            ->where('name', 'Alice')
            ->toRawSql();

        $this->assertStringContainsString("'Alice'", $rawSql);
    }

    // ---------------------------------------------------------------
    // ERRORS
    // ---------------------------------------------------------------

    public function testThrowsWithoutTable(): void
    {
        $this->expectException(\Sniper\Exceptions\SniperException::class);
        $this->expectExceptionMessage('No table specified');

        $this->builder->insert(['name' => 'test']);
    }
}

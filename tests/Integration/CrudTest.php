<?php

declare(strict_types=1);

namespace Sniper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sniper\Connection;
use Sniper\Grammar\SqliteGrammar;
use Sniper\QueryBuilder;
use Sniper\Sniper;

final class CrudTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->connection->statement('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, body TEXT, views INTEGER DEFAULT 0)');
        $this->connection->insert('INSERT INTO posts (title, body, views) VALUES (?, ?, ?)', ['Hello', 'World', 10]);
        $this->connection->insert('INSERT INTO posts (title, body, views) VALUES (?, ?, ?)', ['Foo', 'Bar', 20]);
    }

    private function query(): QueryBuilder
    {
        return new QueryBuilder($this->connection, new SqliteGrammar());
    }

    public function testFullCrudLifecycle(): void
    {
        // Create
        $id = $this->query()->table('posts')->insert(['title' => 'New', 'body' => 'Post', 'views' => 0]);
        $this->assertSame('3', $id);

        // Read
        $post = $this->query()->table('posts')->where('id', 3)->first();
        $this->assertSame('New', $post['title']);

        // Update
        $affected = $this->query()->table('posts')->where('id', 3)->update(['title' => 'Updated']);
        $this->assertSame(1, $affected);

        $updated = $this->query()->table('posts')->where('id', 3)->first();
        $this->assertSame('Updated', $updated['title']);

        // Delete
        $deleted = $this->query()->table('posts')->where('id', 3)->delete();
        $this->assertSame(1, $deleted);
        $this->assertSame(2, $this->query()->table('posts')->count());
    }

    public function testBatchInsert(): void
    {
        $this->query()->table('posts')->insertBatch([
            ['title' => 'A', 'body' => 'a', 'views' => 1],
            ['title' => 'B', 'body' => 'b', 'views' => 2],
        ]);
        $this->assertSame(4, $this->query()->table('posts')->count());
    }

    public function testPaginationIntegration(): void
    {
        $paginator = $this->query()->table('posts')->orderBy('id')->paginate(1, 1);
        $this->assertSame(2, $paginator->total());
        $this->assertSame(1, $paginator->count());
        $this->assertTrue($paginator->hasMorePages());

        $json = $paginator->jsonSerialize();
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('total', $json);
    }
}

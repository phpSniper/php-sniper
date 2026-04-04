<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sniper\Grammar\Grammar;
use Sniper\Grammar\MySqlGrammar;
use Sniper\Grammar\SqliteGrammar;

/**
 * Unit tests for SQL Grammar compilation.
 *
 * Tests that the grammar produces correct SQL and bindings for all query types
 * without requiring a database connection.
 */
final class GrammarTest extends TestCase
{
    // ---------------------------------------------------------------
    // SELECT
    // ---------------------------------------------------------------

    public function testCompilesBasicSelect(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
        ]);

        $this->assertSame('SELECT * FROM `users`', $result['sql']);
        $this->assertEmpty($result['bindings']);
    }

    public function testCompilesSelectWithSpecificColumns(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['name', 'email'],
        ]);

        $this->assertSame('SELECT `name`, `email` FROM `users`', $result['sql']);
    }

    public function testCompilesSelectDistinct(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'    => 'users',
            'columns'  => ['country'],
            'distinct' => true,
        ]);

        $this->assertSame('SELECT DISTINCT `country` FROM `users`', $result['sql']);
    }

    public function testCompilesSelectWithWhere(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'basic', 'column' => 'active', 'operator' => '=', 'value' => 1, 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `active` = ?', $result['sql']);
        $this->assertSame([1], $result['bindings']);
    }

    public function testCompilesSelectWithMultipleWheres(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'basic', 'column' => 'active', 'operator' => '=', 'value' => 1, 'boolean' => 'AND'],
                ['type' => 'basic', 'column' => 'age', 'operator' => '>=', 'value' => 18, 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `active` = ? AND `age` >= ?', $result['sql']);
        $this->assertSame([1, 18], $result['bindings']);
    }

    public function testCompilesWhereIn(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'in', 'column' => 'id', 'values' => [1, 2, 3], 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `id` IN (?, ?, ?)', $result['sql']);
        $this->assertSame([1, 2, 3], $result['bindings']);
    }

    public function testCompilesWhereNotIn(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'notIn', 'column' => 'status', 'values' => ['banned', 'deleted'], 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `status` NOT IN (?, ?)', $result['sql']);
        $this->assertSame(['banned', 'deleted'], $result['bindings']);
    }

    public function testCompilesWhereNull(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'null', 'column' => 'deleted_at', 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `deleted_at` IS NULL', $result['sql']);
        $this->assertEmpty($result['bindings']);
    }

    public function testCompilesWhereNotNull(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'notNull', 'column' => 'email_verified_at', 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `email_verified_at` IS NOT NULL', $result['sql']);
    }

    public function testCompilesWhereBetween(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'products',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'between', 'column' => 'price', 'min' => 10, 'max' => 100, 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `products` WHERE `price` BETWEEN ? AND ?', $result['sql']);
        $this->assertSame([10, 100], $result['bindings']);
    }

    public function testCompilesOrWhere(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'wheres'  => [
                ['type' => 'basic', 'column' => 'role', 'operator' => '=', 'value' => 'admin', 'boolean' => 'AND'],
                ['type' => 'basic', 'column' => 'role', 'operator' => '=', 'value' => 'superadmin', 'boolean' => 'OR'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` WHERE `role` = ? OR `role` = ?', $result['sql']);
        $this->assertSame(['admin', 'superadmin'], $result['bindings']);
    }

    public function testCompilesOrderBy(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'orders'  => [
                ['column' => 'name', 'direction' => 'asc'],
                ['column' => 'created_at', 'direction' => 'desc'],
            ],
        ]);

        $this->assertSame('SELECT * FROM `users` ORDER BY `name` ASC, `created_at` DESC', $result['sql']);
    }

    public function testCompilesLimitAndOffset(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'limit'   => 10,
            'offset'  => 20,
        ]);

        $this->assertSame('SELECT * FROM `users` LIMIT 10 OFFSET 20', $result['sql']);
    }

    public function testCompilesGroupBy(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'orders',
            'columns' => ['status'],
            'groups'  => ['status'],
        ]);

        $this->assertSame('SELECT `status` FROM `orders` GROUP BY `status`', $result['sql']);
    }

    public function testCompilesJoin(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['users.name', 'orders.total'],
            'joins'   => [
                ['type' => 'INNER', 'table' => 'orders', 'first' => 'users.id', 'operator' => '=', 'second' => 'orders.user_id'],
            ],
        ]);

        $this->assertSame(
            'SELECT `users`.`name`, `orders`.`total` FROM `users` INNER JOIN `orders` ON `users`.`id` = `orders`.`user_id`',
            $result['sql'],
        );
    }

    public function testCompilesLeftJoin(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
            'joins'   => [
                ['type' => 'LEFT', 'table' => 'profiles', 'first' => 'users.id', 'operator' => '=', 'second' => 'profiles.user_id'],
            ],
        ]);

        $this->assertStringContainsString('LEFT JOIN `profiles`', $result['sql']);
    }

    // ---------------------------------------------------------------
    // INSERT
    // ---------------------------------------------------------------

    public function testCompilesInsert(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileInsert('users', [
            'name'  => 'John',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('INSERT INTO `users` (`name`, `email`) VALUES (?, ?)', $result['sql']);
        $this->assertSame(['John', 'john@example.com'], $result['bindings']);
    }

    public function testCompilesBatchInsert(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileInsertBatch('users', [
            ['name' => 'Alice', 'email' => 'alice@test.com'],
            ['name' => 'Bob', 'email' => 'bob@test.com'],
        ]);

        $this->assertSame('INSERT INTO `users` (`name`, `email`) VALUES (?, ?), (?, ?)', $result['sql']);
        $this->assertSame(['Alice', 'alice@test.com', 'Bob', 'bob@test.com'], $result['bindings']);
    }

    // ---------------------------------------------------------------
    // UPDATE
    // ---------------------------------------------------------------

    public function testCompilesUpdate(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileUpdate('users', ['name' => 'Jane'], [
            'wheres' => [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 1, 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('UPDATE `users` SET `name` = ? WHERE `id` = ?', $result['sql']);
        $this->assertSame(['Jane', 1], $result['bindings']);
    }

    // ---------------------------------------------------------------
    // DELETE
    // ---------------------------------------------------------------

    public function testCompilesDelete(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileDelete('users', [
            'wheres' => [
                ['type' => 'basic', 'column' => 'id', 'operator' => '=', 'value' => 5, 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('DELETE FROM `users` WHERE `id` = ?', $result['sql']);
        $this->assertSame([5], $result['bindings']);
    }

    // ---------------------------------------------------------------
    // Aggregates
    // ---------------------------------------------------------------

    public function testCompilesCount(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileAggregate('COUNT', '*', [
            'table' => 'users',
        ]);

        $this->assertSame('SELECT COUNT(*) AS aggregate FROM `users`', $result['sql']);
    }

    public function testCompilesSum(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileAggregate('SUM', 'amount', [
            'table' => 'orders',
            'wheres' => [
                ['type' => 'basic', 'column' => 'status', 'operator' => '=', 'value' => 'completed', 'boolean' => 'AND'],
            ],
        ]);

        $this->assertSame('SELECT SUM(`amount`) AS aggregate FROM `orders` WHERE `status` = ?', $result['sql']);
        $this->assertSame(['completed'], $result['bindings']);
    }

    // ---------------------------------------------------------------
    // Table Prefix
    // ---------------------------------------------------------------

    public function testAppliesTablePrefix(): void
    {
        $grammar = new MySqlGrammar();
        $grammar->setTablePrefix('wp_');

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['*'],
        ]);

        $this->assertSame('SELECT * FROM `wp_users`', $result['sql']);
    }

    // ---------------------------------------------------------------
    // Column Aliases and Dot Notation
    // ---------------------------------------------------------------

    public function testWrapsColumnWithDotNotation(): void
    {
        $grammar = new MySqlGrammar();

        $this->assertSame('`users`.`name`', $grammar->wrapColumn('users.name'));
    }

    public function testWrapsColumnWithAlias(): void
    {
        $grammar = new MySqlGrammar();

        $this->assertSame('`name` AS `full_name`', $grammar->wrapColumn('name AS full_name'));
    }

    public function testWrapsStar(): void
    {
        $grammar = new MySqlGrammar();

        $this->assertSame('*', $grammar->wrapColumn('*'));
    }

    public function testWrapsDotStar(): void
    {
        $grammar = new MySqlGrammar();

        $this->assertSame('`users`.*', $grammar->wrapColumn('users.*'));
    }

    // ---------------------------------------------------------------
    // SQLite Grammar
    // ---------------------------------------------------------------

    public function testSqliteUsesDoubleQuoteIdentifiers(): void
    {
        $grammar = new SqliteGrammar();

        $result = $grammar->compileSelect([
            'table'   => 'users',
            'columns' => ['name'],
        ]);

        $this->assertSame('SELECT "name" FROM "users"', $result['sql']);
    }

    // ---------------------------------------------------------------
    // MySQL-specific
    // ---------------------------------------------------------------

    public function testCompilesInsertIgnore(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileInsertIgnore('users', ['name' => 'John']);

        $this->assertStringContainsString('INSERT IGNORE INTO', $result['sql']);
    }

    public function testCompilesUpsert(): void
    {
        $grammar = new MySqlGrammar();

        $result = $grammar->compileUpsert(
            'users',
            ['name' => 'John', 'email' => 'john@test.com'],
            ['name' => 'John Updated'],
        );

        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $result['sql']);
        $this->assertSame(['John', 'john@test.com', 'John Updated'], $result['bindings']);
    }
}

<?php

declare(strict_types=1);

namespace Sniper;

use Sniper\Grammar\Grammar;
use Sniper\Pagination\Paginator;

/**
 * Fluent SQL query builder.
 *
 * Provides a chainable, expressive API for constructing SQL queries.
 * Every value is parameterized via PDO prepared statements — there is
 * zero string concatenation of user-supplied data.
 *
 * @example
 * ```php
 * // SELECT with conditions
 * $users = $builder
 *     ->table('users')
 *     ->select('name', 'email')
 *     ->where('active', true)
 *     ->where('age', '>=', 18)
 *     ->orderBy('name')
 *     ->limit(20)
 *     ->get();
 *
 * // INSERT
 * $builder->table('users')->insert([
 *     'name'  => 'Jane Doe',
 *     'email' => 'jane@example.com',
 * ]);
 *
 * // UPDATE
 * $builder->table('users')->where('id', 1)->update(['name' => 'Updated']);
 *
 * // DELETE
 * $builder->table('users')->where('id', 1)->delete();
 * ```
 */
class QueryBuilder
{
    /**
     * The target table for the query.
     */
    private ?string $table = null;

    /**
     * Whether to select DISTINCT rows.
     */
    private bool $distinct = false;

    /**
     * The columns to SELECT.
     *
     * @var list<string>
     */
    private array $columns = [];

    /**
     * The WHERE clause components.
     *
     * Each entry is an associative array with 'type', 'column', 'operator',
     * 'value', 'boolean', etc. depending on the WHERE variant.
     *
     * @var list<array<string, mixed>>
     */
    private array $wheres = [];

    /**
     * The ORDER BY components.
     *
     * @var list<array{column: string, direction: string}>
     */
    private array $orders = [];

    /**
     * The GROUP BY columns.
     *
     * @var list<string>
     */
    private array $groups = [];

    /**
     * The raw HAVING clause.
     */
    private ?string $having = null;

    /**
     * The HAVING clause bindings.
     *
     * @var array<int, mixed>
     */
    private array $havingBindings = [];

    /**
     * The JOIN components.
     *
     * @var list<array{type: string, table: string, first: string, operator: string, second: string}>
     */
    private array $joins = [];

    /**
     * The LIMIT value.
     */
    private ?int $limit = null;

    /**
     * The OFFSET value.
     */
    private ?int $offset = null;

    /**
     * Create a new QueryBuilder instance.
     *
     * @param Connection $connection The database connection.
     * @param Grammar $grammar The SQL grammar compiler.
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly Grammar $grammar,
    ) {
    }

    /**
     * Set the target table for the query.
     *
     * @param string $table The table name (optionally with alias: "users AS u").
     * @return static
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the columns to select.
     *
     * @param string ...$columns The column names.
     * @return static
     */
    public function select(string ...$columns): static
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Set the query to return DISTINCT results.
     *
     * @return static
     */
    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    // ---------------------------------------------------------------
    // WHERE Clauses
    // ---------------------------------------------------------------

    /**
     * Add a basic WHERE condition.
     *
     * Supports two signatures:
     * - `where('column', 'value')` — defaults to `=` operator
     * - `where('column', 'operator', 'value')` — explicit operator
     *
     * @param string $column The column name.
     * @param mixed $operatorOrValue The operator (if 3 args) or value (if 2 args).
     * @param mixed $value The value (only used if 3 args).
     * @return static
     */
    public function where(string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        [$operator, $resolvedValue] = $this->resolveOperatorAndValue($operatorOrValue, $value);

        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $resolvedValue,
            'boolean'  => 'AND',
        ];

        return $this;
    }

    /**
     * Add an OR WHERE condition.
     *
     * @param string $column The column name.
     * @param mixed $operatorOrValue The operator or value.
     * @param mixed $value The value (optional).
     * @return static
     */
    public function orWhere(string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        [$operator, $resolvedValue] = $this->resolveOperatorAndValue($operatorOrValue, $value);

        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $resolvedValue,
            'boolean'  => 'OR',
        ];

        return $this;
    }

    /**
     * Add a WHERE IN condition.
     *
     * @param string $column The column name.
     * @param list<mixed> $values The allowed values.
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type'    => 'in',
            'column'  => $column,
            'values'  => $values,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT IN condition.
     *
     * @param string $column The column name.
     * @param list<mixed> $values The disallowed values.
     * @return static
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type'    => 'notIn',
            'column'  => $column,
            'values'  => $values,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE IS NULL condition.
     *
     * @param string $column The column name.
     * @return static
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = [
            'type'    => 'null',
            'column'  => $column,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE IS NOT NULL condition.
     *
     * @param string $column The column name.
     * @return static
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = [
            'type'    => 'notNull',
            'column'  => $column,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE BETWEEN condition.
     *
     * @param string $column The column name.
     * @param mixed $min The minimum value.
     * @param mixed $max The maximum value.
     * @return static
     */
    public function whereBetween(string $column, mixed $min, mixed $max): static
    {
        $this->wheres[] = [
            'type'    => 'between',
            'column'  => $column,
            'min'     => $min,
            'max'     => $max,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a raw WHERE clause (escape hatch for complex conditions).
     *
     * ⚠️ Use with caution: the SQL string is injected as-is.
     * Always use parameterized bindings for any user-supplied values.
     *
     * @param string $sql The raw SQL condition.
     * @param array<int, mixed> $bindings The parameter bindings.
     * @return static
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->wheres[] = [
            'type'     => 'raw',
            'sql'      => $sql,
            'bindings' => $bindings,
            'boolean'  => 'AND',
        ];

        return $this;
    }

    // ---------------------------------------------------------------
    // JOINs
    // ---------------------------------------------------------------

    /**
     * Add an INNER JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $first The first column (from the main table).
     * @param string $operator The comparison operator.
     * @param string $second The second column (from the joined table).
     * @return static
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type'     => 'INNER',
            'table'    => $table,
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $first The first column.
     * @param string $operator The comparison operator.
     * @param string $second The second column.
     * @return static
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type'     => 'LEFT',
            'table'    => $table,
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
        ];

        return $this;
    }

    /**
     * Add a RIGHT JOIN clause.
     *
     * @param string $table The table to join.
     * @param string $first The first column.
     * @param string $operator The comparison operator.
     * @param string $second The second column.
     * @return static
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type'     => 'RIGHT',
            'table'    => $table,
            'first'    => $first,
            'operator' => $operator,
            'second'   => $second,
        ];

        return $this;
    }

    // ---------------------------------------------------------------
    // ORDER BY, GROUP BY, HAVING, LIMIT, OFFSET
    // ---------------------------------------------------------------

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column The column to order by.
     * @param string $direction The sort direction ("asc" or "desc").
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $this->orders[] = [
            'column'    => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Shortcut: ORDER BY column DESC (newest first).
     *
     * @param string $column The column to order by.
     * @return static
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Shortcut: ORDER BY column ASC (oldest first).
     *
     * @param string $column The column to order by.
     * @return static
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Add a GROUP BY clause.
     *
     * @param string ...$columns The columns to group by.
     * @return static
     */
    public function groupBy(string ...$columns): static
    {
        $this->groups = array_merge($this->groups, array_values($columns));

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * @param string $sql The raw HAVING condition.
     * @param array<int, mixed> $bindings The parameter bindings.
     * @return static
     */
    public function having(string $sql, array $bindings = []): static
    {
        $this->having = $sql;
        $this->havingBindings = array_values($bindings);

        return $this;
    }

    /**
     * Set the LIMIT value.
     *
     * @param int $limit Maximum number of rows to return.
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limit = max(0, $limit);

        return $this;
    }

    /**
     * Alias for limit() — reads more naturally in some contexts.
     *
     * @param int $count Number of rows.
     * @return static
     */
    public function take(int $count): static
    {
        return $this->limit($count);
    }

    /**
     * Set the OFFSET value.
     *
     * @param int $offset Number of rows to skip.
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offset = max(0, $offset);

        return $this;
    }

    /**
     * Alias for offset() — reads more naturally in some contexts.
     *
     * @param int $count Number of rows to skip.
     * @return static
     */
    public function skip(int $count): static
    {
        return $this->offset($count);
    }

    // ---------------------------------------------------------------
    // Execution: SELECT
    // ---------------------------------------------------------------

    /**
     * Execute the query and return all results as a Collection.
     *
     * @return Collection<array<string, mixed>>
     */
    public function get(): Collection
    {
        $compiled = $this->grammar->compileSelect($this->getComponents());
        $rows = $this->connection->select($compiled['sql'], $compiled['bindings']);

        return new Collection($rows);
    }

    /**
     * Execute the query and return only the first row.
     *
     * @return array<string, mixed>|null The first row, or null if empty.
     */
    public function first(): ?array
    {
        $this->limit(1);
        $compiled = $this->grammar->compileSelect($this->getComponents());

        return $this->connection->selectOne($compiled['sql'], $compiled['bindings']);
    }

    /**
     * Execute the query and return a single column value from the first row.
     *
     * @param string $column The column to retrieve.
     * @return mixed The column value, or null if no results.
     */
    public function value(string $column): mixed
    {
        $row = $this->select($column)->first();

        return $row[$column] ?? null;
    }

    /**
     * Execute the query and return an array of values from a single column.
     *
     * @param string $column The column to pluck.
     * @return Collection<mixed>
     */
    public function pluck(string $column): Collection
    {
        $results = $this->select($column)->get();

        return new Collection(array_column($results->toArray(), $column));
    }

    /**
     * Check if any rows exist matching the current query.
     *
     * @return bool True if at least one row exists.
     */
    public function exists(): bool
    {
        $compiled = $this->grammar->compileExists($this->getComponents());
        $row = $this->connection->selectOne($compiled['sql'], $compiled['bindings']);

        return $row !== null && (bool) ($row['exists'] ?? false);
    }

    /**
     * Check if no rows exist matching the current query.
     *
     * @return bool True if no rows match the query.
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    // ---------------------------------------------------------------
    // Execution: Aggregates
    // ---------------------------------------------------------------

    /**
     * Get the count of rows matching the query.
     *
     * @param string $column The column to count (default: "*").
     * @return int The row count.
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * Get the sum of a column.
     *
     * @param string $column The column to sum.
     * @return float|int The sum.
     */
    public function sum(string $column): float|int
    {
        return $this->aggregate('SUM', $column) ?? 0;
    }

    /**
     * Get the average of a column.
     *
     * @param string $column The column to average.
     * @return float|null The average, or null if no rows.
     */
    public function avg(string $column): float|null
    {
        $result = $this->aggregate('AVG', $column);

        return $result !== null ? (float) $result : null;
    }

    /**
     * Get the minimum value of a column.
     *
     * @param string $column The column.
     * @return mixed The minimum value.
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get the maximum value of a column.
     *
     * @param string $column The column.
     * @return mixed The maximum value.
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    // ---------------------------------------------------------------
    // Execution: INSERT
    // ---------------------------------------------------------------

    /**
     * Insert a row into the table.
     *
     * @param array<string, mixed> $data Column => value pairs to insert.
     * @return string The last inserted auto-increment ID.
     */
    public function insert(array $data): string
    {
        $table = $this->getTable();

        $compiled = $this->grammar->compileInsert($table, $data);

        return $this->connection->insert($compiled['sql'], $compiled['bindings']);
    }

    /**
     * Insert multiple rows in a single query.
     *
     * @param array<int, array<string, mixed>> $rows Array of column => value pairs.
     * @return bool True if the batch insert succeeded.
     */
    public function insertBatch(array $rows): bool
    {
        $table = $this->getTable();

        if (empty($rows)) {
            return true;
        }

        $compiled = $this->grammar->compileInsertBatch($table, $rows);

        return $this->connection->statement($compiled['sql'], $compiled['bindings']);
    }

    // ---------------------------------------------------------------
    // Execution: UPDATE
    // ---------------------------------------------------------------

    /**
     * Update rows matching the current WHERE conditions.
     *
     * @param array<string, mixed> $data Column => value pairs to update.
     * @return int The number of affected rows.
     */
    public function update(array $data): int
    {
        $table = $this->getTable();

        $compiled = $this->grammar->compileUpdate($table, $data, $this->getComponents());

        return $this->connection->affectingStatement($compiled['sql'], $compiled['bindings']);
    }

    /**
     * Increment a numeric column by a given amount.
     *
     * @param string $column The column to increment.
     * @param int|float $amount The amount to increment by.
     * @param array<string, mixed> $extra Additional columns to update.
     * @return int The number of affected rows.
     */
    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $tableName = $this->getTable();

        $wrappedColumn = $this->grammar->wrapColumn($column);

        // Build the SET clause manually for the increment expression
        $bindings = [];
        $setClauses = ["{$wrappedColumn} = {$wrappedColumn} + ?"];
        $bindings[] = $amount;

        foreach ($extra as $key => $value) {
            $setClauses[] = $this->grammar->wrapColumn($key) . ' = ?';
            $bindings[] = $value;
        }

        $wrappedTable = $this->grammar->wrapTable($tableName);
        $sql = "UPDATE {$wrappedTable} SET " . implode(', ', $setClauses);

        // Compile and append WHERE clause
        $components = $this->getComponents();
        $whereResult = $this->grammar->compileDelete($tableName, $components);
        $whereSql = str_replace("DELETE FROM {$wrappedTable}", '', $whereResult['sql']);

        $sql .= $whereSql;
        $bindings = array_merge($bindings, $whereResult['bindings']);

        return $this->connection->affectingStatement($sql, $bindings);
    }

    /**
     * Decrement a numeric column by a given amount.
     *
     * @param string $column The column to decrement.
     * @param int|float $amount The amount to decrement by.
     * @param array<string, mixed> $extra Additional columns to update.
     * @return int The number of affected rows.
     */
    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }

    // ---------------------------------------------------------------
    // Execution: DELETE
    // ---------------------------------------------------------------

    /**
     * Delete rows matching the current WHERE conditions.
     *
     * @return int The number of deleted rows.
     */
    public function delete(): int
    {
        $table = $this->getTable();

        $compiled = $this->grammar->compileDelete($table, $this->getComponents());

        return $this->connection->affectingStatement($compiled['sql'], $compiled['bindings']);
    }

    // ---------------------------------------------------------------
    // Pagination
    // ---------------------------------------------------------------

    /**
     * Paginate the results.
     *
     * @param int $perPage Number of items per page.
     * @param int $currentPage The current page number (1-indexed).
     * @return Paginator The paginated result set.
     */
    public function paginate(int $perPage = 15, int $currentPage = 1): Paginator
    {
        $currentPage = max(1, $currentPage);
        $total = $this->count();

        $this->limit($perPage);
        $this->offset(($currentPage - 1) * $perPage);

        $items = $this->get();

        return new Paginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $currentPage,
        );
    }

    // ---------------------------------------------------------------
    // Debugging
    // ---------------------------------------------------------------

    /**
     * Get the compiled SQL and bindings without executing the query.
     *
     * Useful for debugging and logging.
     *
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function toSql(): array
    {
        return $this->grammar->compileSelect($this->getComponents());
    }

    /**
     * Get the compiled SQL with bindings interpolated (for debugging ONLY).
     *
     * ⚠️ Never use this output for actual query execution — it bypasses
     * prepared statement protections.
     *
     * @return string The SQL with values interpolated.
     */
    public function toRawSql(): string
    {
        $compiled = $this->toSql();
        $sql = $compiled['sql'];

        foreach ($compiled['bindings'] as $binding) {
            $value = is_string($binding) ? "'" . addslashes($binding) . "'" : (string) ($binding ?? 'NULL');
            $sql = preg_replace('/\?/', $value, $sql, 1) ?? $sql;
        }

        return $sql;
    }

    // ---------------------------------------------------------------
    // Internal Helpers
    // ---------------------------------------------------------------

    /**
     * Create a new builder instance with the same connection and grammar.
     *
     * @return self
     */
    public function newQuery(): self
    {
        return new self($this->connection, $this->grammar);
    }

    /**
     * Get all builder components as an array for the grammar compiler.
     *
     * @return array<string, mixed>
     */
    private function getComponents(): array
    {
        return [
            'table'          => $this->table,
            'distinct'       => $this->distinct,
            'columns'        => $this->columns,
            'wheres'         => $this->wheres,
            'orders'         => $this->orders,
            'groups'         => $this->groups,
            'having'         => $this->having,
            'havingBindings' => $this->havingBindings,
            'joins'          => $this->joins,
            'limit'          => $this->limit,
            'offset'         => $this->offset,
        ];
    }

    /**
     * Resolve the operator and value from the flexible where() signature.
     *
     * Handles both `where('col', 'val')` and `where('col', '>', 'val')`.
     *
     * @param mixed $operatorOrValue The second argument.
     * @param mixed $value The third argument (null if using 2-arg form).
     * @return array{0: string, 1: mixed} The resolved [operator, value] pair.
     */
    private function resolveOperatorAndValue(mixed $operatorOrValue, mixed $value): array
    {
        if ($value === null) {
            return ['=', $operatorOrValue];
        }

        // Whitelist valid operators to prevent operator injection
        $validOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'like', 'not like'];

        $operator = is_string($operatorOrValue) && in_array(strtoupper($operatorOrValue), array_map('strtoupper', $validOperators), true)
            ? $operatorOrValue
            : '=';

        return [$operator, $value];
    }

    /**
     * Run an aggregate function against the database.
     *
     * @param string $function The aggregate function (COUNT, SUM, AVG, MIN, MAX).
     * @param string $column The column to aggregate.
     * @return mixed The aggregate result.
     */
    private function aggregate(string $function, string $column): mixed
    {
        $compiled = $this->grammar->compileAggregate($function, $column, $this->getComponents());
        $row = $this->connection->selectOne($compiled['sql'], $compiled['bindings']);

        return $row['aggregate'] ?? null;
    }

    /**
     * Get the table name, throwing if none is set.
     *
     * @return string The validated table name.
     * @throws \Sniper\Exceptions\SniperException If no table is set.
     */
    private function getTable(): string
    {
        if ($this->table === null || $this->table === '') {
            throw new \Sniper\Exceptions\SniperException(
                'No table specified. Call ->table("table_name") before executing a query.',
            );
        }

        return $this->table;
    }
}

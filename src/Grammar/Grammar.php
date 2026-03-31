<?php

declare(strict_types=1);

namespace Sniper\Grammar;

/**
 * Base SQL grammar compiler.
 *
 * Compiles the internal query builder state into driver-agnostic SQL strings
 * with parameter bindings. Database-specific dialects (MySQL, SQLite, etc.)
 * extend this class and override methods as needed.
 *
 * The grammar NEVER concatenates user values into SQL — all dynamic values
 * are collected as bindings and passed to PDO prepared statements.
 */
class Grammar
{
    /**
     * The character used to quote identifiers (tables, columns).
     * Overridden by database-specific grammars.
     */
    protected string $quoteCharacter = '"';

    /**
     * The table prefix, applied to all table references.
     */
    protected string $tablePrefix = '';

    /**
     * Set the table prefix.
     *
     * @param string $prefix The prefix to prepend to table names (e.g., "wp_").
     * @return static
     */
    public function setTablePrefix(string $prefix): static
    {
        $this->tablePrefix = $prefix;

        return $this;
    }

    /**
     * Compile a SELECT query from builder components.
     *
     * @param array<string, mixed> $components The query builder state.
     * @return array{sql: string, bindings: array<int, mixed>} Compiled SQL and bindings.
     */
    public function compileSelect(array $components): array
    {
        $bindings = [];

        $sql = $this->compileSelectColumns($components);
        $sql .= $this->compileFrom($components);
        $sql .= $this->compileJoins($components, $bindings);
        $sql .= $this->compileWheres($components, $bindings);
        $sql .= $this->compileGroupBy($components);
        $sql .= $this->compileHaving($components, $bindings);
        $sql .= $this->compileOrderBy($components);
        $sql .= $this->compileLimit($components);
        $sql .= $this->compileOffset($components);

        return ['sql' => trim($sql), 'bindings' => $bindings];
    }

    /**
     * Compile an INSERT query.
     *
     * @param string $table The target table.
     * @param array<string, mixed> $data Column => value pairs to insert.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileInsert(string $table, array $data): array
    {
        $table = $this->wrapTable($table);
        $columns = implode(', ', array_map(
            fn(string $col): string => $this->wrapColumn($col),
            array_keys($data),
        ));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        return ['sql' => $sql, 'bindings' => array_values($data)];
    }

    /**
     * Compile a batch INSERT query for multiple rows.
     *
     * @param string $table The target table.
     * @param array<int, array<string, mixed>> $rows Array of column => value pairs.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileInsertBatch(string $table, array $rows): array
    {
        if (empty($rows)) {
            return ['sql' => '', 'bindings' => []];
        }

        $table = $this->wrapTable($table);
        $columns = array_keys($rows[0]);
        $columnsSql = implode(', ', array_map(
            fn(string $col): string => $this->wrapColumn($col),
            $columns,
        ));

        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $rowPlaceholder));

        $bindings = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $bindings[] = $row[$col] ?? null;
            }
        }

        $sql = "INSERT INTO {$table} ({$columnsSql}) VALUES {$allPlaceholders}";

        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Compile an UPDATE query.
     *
     * @param string $table The target table.
     * @param array<string, mixed> $data Column => value pairs to update.
     * @param array<string, mixed> $components The WHERE/builder state.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileUpdate(string $table, array $data, array $components): array
    {
        $bindings = [];
        $table = $this->wrapTable($table);

        $setClauses = [];
        foreach ($data as $column => $value) {
            $setClauses[] = $this->wrapColumn($column) . ' = ?';
            $bindings[] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setClauses);
        $sql .= $this->compileWheres($components, $bindings);

        return ['sql' => trim($sql), 'bindings' => $bindings];
    }

    /**
     * Compile a DELETE query.
     *
     * @param string $table The target table.
     * @param array<string, mixed> $components The WHERE/builder state.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileDelete(string $table, array $components): array
    {
        $bindings = [];
        $table = $this->wrapTable($table);

        $sql = "DELETE FROM {$table}";
        $sql .= $this->compileWheres($components, $bindings);

        return ['sql' => trim($sql), 'bindings' => $bindings];
    }

    /**
     * Compile an aggregate query (COUNT, SUM, AVG, MIN, MAX).
     *
     * @param string $function The aggregate function name.
     * @param string $column The column to aggregate.
     * @param array<string, mixed> $components The query builder state.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileAggregate(string $function, string $column, array $components): array
    {
        $bindings = [];
        $wrappedColumn = $column === '*' ? '*' : $this->wrapColumn($column);

        $sql = "SELECT {$function}({$wrappedColumn}) AS aggregate";
        $sql .= $this->compileFrom($components);
        $sql .= $this->compileJoins($components, $bindings);
        $sql .= $this->compileWheres($components, $bindings);
        $sql .= $this->compileGroupBy($components);
        $sql .= $this->compileHaving($components, $bindings);

        return ['sql' => trim($sql), 'bindings' => $bindings];
    }

    /**
     * Compile an EXISTS subquery.
     *
     * @param array<string, mixed> $components The query builder state.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileExists(array $components): array
    {
        $innerResult = $this->compileSelect($components);

        $sql = "SELECT EXISTS ({$innerResult['sql']}) AS {$this->wrapColumn('exists')}";

        return ['sql' => $sql, 'bindings' => $innerResult['bindings']];
    }

    /**
     * Wrap a table name with the appropriate quote characters and prefix.
     *
     * @param string $table The raw table name (may include alias: "users AS u").
     * @return string The quoted table reference.
     */
    public function wrapTable(string $table): string
    {
        if (str_contains($table, ' AS ') || str_contains($table, ' as ')) {
            $parts = preg_split('/\s+[Aa][Ss]\s+/', $table, 2);

            if ($parts === false || count($parts) !== 2) {
                return $this->quoteIdentifier($this->tablePrefix . $table);
            }

            return $this->quoteIdentifier($this->tablePrefix . trim($parts[0]))
                . ' AS '
                . $this->quoteIdentifier(trim($parts[1]));
        }

        return $this->quoteIdentifier($this->tablePrefix . $table);
    }

    /**
     * Wrap a column name with the appropriate quote characters.
     *
     * Handles dot notation (e.g., "users.name") and aliases ("name AS full_name").
     *
     * @param string $column The raw column name.
     * @return string The quoted column reference.
     */
    public function wrapColumn(string $column): string
    {
        if ($column === '*') {
            return '*';
        }

        // Handle aliases: "column AS alias"
        if (str_contains($column, ' AS ') || str_contains($column, ' as ')) {
            $parts = preg_split('/\s+[Aa][Ss]\s+/', $column, 2);

            if ($parts !== false && count($parts) === 2) {
                return $this->wrapColumn(trim($parts[0]))
                    . ' AS '
                    . $this->quoteIdentifier(trim($parts[1]));
            }
        }

        // Handle dot notation: "table.column"
        if (str_contains($column, '.')) {
            $segments = explode('.', $column, 2);

            return $this->quoteIdentifier($this->tablePrefix . $segments[0])
                . '.'
                . ($segments[1] === '*' ? '*' : $this->quoteIdentifier($segments[1]));
        }

        return $this->quoteIdentifier($column);
    }

    /**
     * Quote a single identifier with the grammar's quote character.
     *
     * @param string $identifier The raw identifier.
     * @return string The quoted identifier.
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return $this->quoteCharacter . $identifier . $this->quoteCharacter;
    }

    /**
     * Compile the SELECT column list.
     *
     * @param array<string, mixed> $components The builder state.
     * @return string The "SELECT col1, col2" clause.
     */
    private function compileSelectColumns(array $components): string
    {
        /** @var bool $distinct */
        $distinct = $components['distinct'] ?? false;
        $keyword = $distinct ? 'SELECT DISTINCT' : 'SELECT';

        /** @var list<string> $columns */
        $columns = $components['columns'] ?? ['*'];

        if (empty($columns)) {
            $columns = ['*'];
        }

        $wrapped = array_map(
            fn(string $col): string => $this->wrapColumn($col),
            $columns,
        );

        return $keyword . ' ' . implode(', ', $wrapped);
    }

    /**
     * Compile the FROM clause.
     *
     * @param array<string, mixed> $components The builder state.
     * @return string The " FROM table" clause.
     */
    private function compileFrom(array $components): string
    {
        /** @var string|null $table */
        $table = $components['table'] ?? null;

        if ($table === null) {
            return '';
        }

        return ' FROM ' . $this->wrapTable($table);
    }

    /**
     * Compile JOIN clauses.
     *
     * @param array<string, mixed> $components The builder state.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled JOIN clauses.
     */
    private function compileJoins(array $components, array &$bindings): string
    {
        /** @var list<array{type: string, table: string, first: string, operator: string, second: string}> $joins */
        $joins = $components['joins'] ?? [];

        if (empty($joins)) {
            return '';
        }

        $sql = '';
        foreach ($joins as $join) {
            $table = $this->wrapTable($join['table']);
            $first = $this->wrapColumn($join['first']);
            $second = $this->wrapColumn($join['second']);

            $sql .= " {$join['type']} JOIN {$table} ON {$first} {$join['operator']} {$second}";
        }

        return $sql;
    }

    /**
     * Compile WHERE clauses into SQL with parameterized bindings.
     *
     * @param array<string, mixed> $components The builder state.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled " WHERE ..." clause.
     */
    private function compileWheres(array $components, array &$bindings): string
    {
        /** @var list<array<string, mixed>> $wheres */
        $wheres = $components['wheres'] ?? [];

        if (empty($wheres)) {
            return '';
        }

        $clauses = [];
        foreach ($wheres as $index => $where) {
            /** @var string $type */
            $type = $where['type'];
            /** @var string $boolean */
            $boolean = $where['boolean'] ?? 'AND';

            $clause = match ($type) {
                'basic' => $this->compileWhereBasic($where, $bindings),
                'in' => $this->compileWhereIn($where, $bindings),
                'notIn' => $this->compileWhereNotIn($where, $bindings),
                'null' => $this->compileWhereNull($where),
                'notNull' => $this->compileWhereNotNull($where),
                'between' => $this->compileWhereBetween($where, $bindings),
                'raw' => $this->compileWhereRaw($where, $bindings),
                default => '',
            };

            if ($clause !== '') {
                $clauses[] = ($index === 0 ? '' : $boolean . ' ') . $clause;
            }
        }

        if (empty($clauses)) {
            return '';
        }

        return ' WHERE ' . implode(' ', $clauses);
    }

    /**
     * Compile a basic "column operator value" WHERE clause.
     *
     * @param array<string, mixed> $where The WHERE component.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled clause (e.g., `"name" = ?`).
     */
    private function compileWhereBasic(array $where, array &$bindings): string
    {
        /** @var string $column */
        $column = $where['column'];
        /** @var string $operator */
        $operator = $where['operator'];
        /** @var mixed $value */
        $value = $where['value'];

        $bindings[] = $value;

        return $this->wrapColumn($column) . ' ' . $operator . ' ?';
    }

    /**
     * Compile a WHERE IN clause.
     *
     * @param array<string, mixed> $where The WHERE component.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled clause (e.g., `"id" IN (?, ?, ?)`).
     */
    private function compileWhereIn(array $where, array &$bindings): string
    {
        /** @var string $column */
        $column = $where['column'];
        /** @var list<mixed> $values */
        $values = $where['values'];

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        array_push($bindings, ...$values);

        return $this->wrapColumn($column) . " IN ({$placeholders})";
    }

    /**
     * Compile a WHERE NOT IN clause.
     *
     * @param array<string, mixed> $where The WHERE component.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled clause.
     */
    private function compileWhereNotIn(array $where, array &$bindings): string
    {
        /** @var string $column */
        $column = $where['column'];
        /** @var list<mixed> $values */
        $values = $where['values'];

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        array_push($bindings, ...$values);

        return $this->wrapColumn($column) . " NOT IN ({$placeholders})";
    }

    /**
     * Compile a WHERE IS NULL clause.
     *
     * @param array<string, mixed> $where The WHERE component.
     * @return string The compiled clause.
     */
    private function compileWhereNull(array $where): string
    {
        /** @var string $column */
        $column = $where['column'];

        return $this->wrapColumn($column) . ' IS NULL';
    }

    /**
     * Compile a WHERE IS NOT NULL clause.
     *
     * @param array<string, mixed> $where The WHERE component.
     * @return string The compiled clause.
     */
    private function compileWhereNotNull(array $where): string
    {
        /** @var string $column */
        $column = $where['column'];

        return $this->wrapColumn($column) . ' IS NOT NULL';
    }

    /**
     * Compile a WHERE BETWEEN clause.
     *
     * @param array<string, mixed> $where The WHERE component.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled clause.
     */
    private function compileWhereBetween(array $where, array &$bindings): string
    {
        /** @var string $column */
        $column = $where['column'];
        /** @var mixed $min */
        $min = $where['min'];
        /** @var mixed $max */
        $max = $where['max'];

        $bindings[] = $min;
        $bindings[] = $max;

        return $this->wrapColumn($column) . ' BETWEEN ? AND ?';
    }

    /**
     * Compile a raw WHERE clause (escape hatch for complex conditions).
     *
     * @param array<string, mixed> $where The WHERE component.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The raw SQL clause.
     */
    private function compileWhereRaw(array $where, array &$bindings): string
    {
        /** @var string $sql */
        $sql = $where['sql'];
        /** @var list<mixed> $rawBindings */
        $rawBindings = $where['bindings'] ?? [];

        array_push($bindings, ...$rawBindings);

        return $sql;
    }

    /**
     * Compile the GROUP BY clause.
     *
     * @param array<string, mixed> $components The builder state.
     * @return string The compiled GROUP BY clause.
     */
    private function compileGroupBy(array $components): string
    {
        /** @var list<string> $groups */
        $groups = $components['groups'] ?? [];

        if (empty($groups)) {
            return '';
        }

        $wrapped = array_map(
            fn(string $col): string => $this->wrapColumn($col),
            $groups,
        );

        return ' GROUP BY ' . implode(', ', $wrapped);
    }

    /**
     * Compile the HAVING clause.
     *
     * @param array<string, mixed> $components The builder state.
     * @param array<int, mixed> &$bindings Bindings array (mutated).
     * @return string The compiled HAVING clause.
     */
    private function compileHaving(array $components, array &$bindings): string
    {
        /** @var string|null $having */
        $having = $components['having'] ?? null;

        if ($having === null) {
            return '';
        }

        /** @var list<mixed> $havingBindings */
        $havingBindings = $components['havingBindings'] ?? [];
        array_push($bindings, ...$havingBindings);

        return ' HAVING ' . $having;
    }

    /**
     * Compile the ORDER BY clause.
     *
     * @param array<string, mixed> $components The builder state.
     * @return string The compiled ORDER BY clause.
     */
    private function compileOrderBy(array $components): string
    {
        /** @var list<array{column: string, direction: string}> $orders */
        $orders = $components['orders'] ?? [];

        if (empty($orders)) {
            return '';
        }

        $clauses = array_map(
            fn(array $order): string => $this->wrapColumn($order['column']) . ' ' . strtoupper($order['direction']),
            $orders,
        );

        return ' ORDER BY ' . implode(', ', $clauses);
    }

    /**
     * Compile the LIMIT clause.
     *
     * @param array<string, mixed> $components The builder state.
     * @return string The compiled LIMIT clause.
     */
    private function compileLimit(array $components): string
    {
        /** @var int|null $limit */
        $limit = $components['limit'] ?? null;

        if ($limit === null) {
            return '';
        }

        return ' LIMIT ' . $limit;
    }

    /**
     * Compile the OFFSET clause.
     *
     * @param array<string, mixed> $components The builder state.
     * @return string The compiled OFFSET clause.
     */
    private function compileOffset(array $components): string
    {
        /** @var int|null $offset */
        $offset = $components['offset'] ?? null;

        if ($offset === null) {
            return '';
        }

        return ' OFFSET ' . $offset;
    }
}

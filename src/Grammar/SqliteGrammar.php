<?php

declare(strict_types=1);

namespace Sniper\Grammar;

/**
 * SQLite-specific SQL grammar.
 *
 * Overrides the base grammar with SQLite-compatible identifier quoting
 * and any SQLite-specific syntax. Used primarily for testing with
 * in-memory databases (`:memory:`).
 */
class SqliteGrammar extends Grammar
{
    /**
     * SQLite uses double-quotes for identifiers (same as ANSI SQL).
     */
    protected string $quoteCharacter = '"';

    /**
     * Compile an INSERT OR IGNORE query (SQLite-specific).
     *
     * @param string $table The target table.
     * @param array<string, mixed> $data Column => value pairs to insert.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileInsertIgnore(string $table, array $data): array
    {
        $result = $this->compileInsert($table, $data);
        $result['sql'] = str_replace('INSERT INTO', 'INSERT OR IGNORE INTO', $result['sql']);

        return $result;
    }

    /**
     * Compile an INSERT ... ON CONFLICT DO UPDATE query (SQLite upsert).
     *
     * @param string $table The target table.
     * @param array<string, mixed> $data Column => value pairs to insert.
     * @param array<string, mixed> $updateData Column => value pairs to update on conflict.
     * @param string $conflictColumn The column that triggers the conflict (e.g., primary key).
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileUpsert(
        string $table,
        array $data,
        array $updateData,
        string $conflictColumn = 'id',
    ): array {
        $insertResult = $this->compileInsert($table, $data);

        $setClauses = [];
        $updateBindings = [];
        foreach ($updateData as $column => $value) {
            $setClauses[] = $this->wrapColumn($column) . ' = ?';
            $updateBindings[] = $value;
        }

        $conflictCol = $this->wrapColumn($conflictColumn);
        $insertResult['sql'] .= " ON CONFLICT ({$conflictCol}) DO UPDATE SET " . implode(', ', $setClauses);
        $insertResult['bindings'] = array_merge($insertResult['bindings'], $updateBindings);

        return $insertResult;
    }
}

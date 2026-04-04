<?php

declare(strict_types=1);

namespace Sniper\Grammar;

/**
 * MySQL-specific SQL grammar.
 *
 * Overrides the base grammar to use MySQL's backtick identifier quoting
 * and any MySQL-specific SQL syntax quirks.
 */
class MySqlGrammar extends Grammar
{
    /**
     * MySQL uses backticks to quote identifiers.
     */
    protected string $quoteCharacter = '`';

    /**
     * Compile an INSERT IGNORE query (MySQL-specific).
     *
     * @param string $table The target table.
     * @param array<string, mixed> $data Column => value pairs to insert.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileInsertIgnore(string $table, array $data): array
    {
        $result = $this->compileInsert($table, $data);
        $result['sql'] = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $result['sql']);

        return $result;
    }

    /**
     * Compile an INSERT ... ON DUPLICATE KEY UPDATE query (MySQL upsert).
     *
     * @param string $table The target table.
     * @param array<string, mixed> $data Column => value pairs to insert.
     * @param array<string, mixed> $updateData Column => value pairs to update on conflict.
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function compileUpsert(string $table, array $data, array $updateData): array
    {
        $insertResult = $this->compileInsert($table, $data);

        $setClauses = [];
        $updateBindings = [];
        foreach ($updateData as $column => $value) {
            $setClauses[] = $this->wrapColumn($column) . ' = ?';
            $updateBindings[] = $value;
        }

        $insertResult['sql'] .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $setClauses);
        $insertResult['bindings'] = array_merge($insertResult['bindings'], $updateBindings);

        return $insertResult;
    }
}

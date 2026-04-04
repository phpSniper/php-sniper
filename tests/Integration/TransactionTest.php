<?php

declare(strict_types=1);

namespace Sniper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sniper\Connection;

final class TransactionTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = Connection::create(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->connection->statement('CREATE TABLE accounts (id INTEGER PRIMARY KEY, balance INTEGER)');
        $this->connection->insert('INSERT INTO accounts (id, balance) VALUES (?, ?)', [1, 1000]);
        $this->connection->insert('INSERT INTO accounts (id, balance) VALUES (?, ?)', [2, 1000]);
    }

    public function testTransactionCommitsOnSuccess(): void
    {
        $this->connection->transaction(function (): void {
            $this->connection->affectingStatement('UPDATE accounts SET balance = ? WHERE id = ?', [900, 1]);
            $this->connection->affectingStatement('UPDATE accounts SET balance = ? WHERE id = ?', [1100, 2]);
        });

        $row1 = $this->connection->selectOne('SELECT balance FROM accounts WHERE id = ?', [1]);
        $row2 = $this->connection->selectOne('SELECT balance FROM accounts WHERE id = ?', [2]);

        $this->assertSame(900, $row1['balance']);
        $this->assertSame(1100, $row2['balance']);
    }

    public function testTransactionRollsBackOnException(): void
    {
        try {
            $this->connection->transaction(function (): void {
                $this->connection->affectingStatement('UPDATE accounts SET balance = ? WHERE id = ?', [500, 1]);
                throw new \RuntimeException('Simulated failure');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $row = $this->connection->selectOne('SELECT balance FROM accounts WHERE id = ?', [1]);
        $this->assertSame(1000, $row['balance']); // Rolled back
    }

    public function testTransactionReturnsCallbackValue(): void
    {
        $result = $this->connection->transaction(fn(): string => 'success');
        $this->assertSame('success', $result);
    }

    public function testNestedTransactions(): void
    {
        $this->connection->beginTransaction();
        $this->assertSame(1, $this->connection->getTransactionDepth());

        $this->connection->beginTransaction(); // Savepoint
        $this->assertSame(2, $this->connection->getTransactionDepth());

        $this->connection->commit(); // Release savepoint
        $this->assertSame(1, $this->connection->getTransactionDepth());

        $this->connection->commit(); // Commit outer
        $this->assertSame(0, $this->connection->getTransactionDepth());
    }
}

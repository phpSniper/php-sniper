<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sniper\Collection;

/**
 * Unit tests for the Collection class.
 *
 * Verifies all collection operations (map, filter, pluck, etc.)
 * and interface implementations.
 */
final class CollectionTest extends TestCase
{
    public function testCreatesEmptyCollection(): void
    {
        $collection = new Collection();

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
        $this->assertSame(0, $collection->count());
    }

    public function testCreatesCollectionFromArray(): void
    {
        $items = [1, 2, 3];
        $collection = new Collection($items);

        $this->assertSame($items, $collection->toArray());
        $this->assertSame(3, $collection->count());
    }

    public function testFirst(): void
    {
        $collection = new Collection([10, 20, 30]);

        $this->assertSame(10, $collection->first());
    }

    public function testFirstOnEmpty(): void
    {
        $collection = new Collection();

        $this->assertNull($collection->first());
    }

    public function testLast(): void
    {
        $collection = new Collection([10, 20, 30]);

        $this->assertSame(30, $collection->last());
    }

    public function testLastOnEmpty(): void
    {
        $collection = new Collection();

        $this->assertNull($collection->last());
    }

    public function testMap(): void
    {
        $collection = new Collection([1, 2, 3]);
        $doubled = $collection->map(fn(int $item): int => $item * 2);

        $this->assertSame([2, 4, 6], $doubled->toArray());
    }

    public function testFilter(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $even = $collection->filter(fn(int $item): bool => $item % 2 === 0);

        $this->assertSame([2, 4], array_values($even->toArray()));
    }

    public function testFilterWithoutCallback(): void
    {
        $collection = new Collection([0, 1, '', 'hello', null, true, false]);
        $truthy = $collection->filter();

        $this->assertSame(3, $truthy->count());
    }

    public function testPluck(): void
    {
        $collection = new Collection([
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ]);

        $names = $collection->pluck('name');

        $this->assertSame(['Alice', 'Bob'], $names->toArray());
    }

    public function testGroupBy(): void
    {
        $collection = new Collection([
            ['city' => 'NYC', 'name' => 'Alice'],
            ['city' => 'LA', 'name' => 'Bob'],
            ['city' => 'NYC', 'name' => 'Charlie'],
        ]);

        $grouped = $collection->groupBy('city');
        $groupedArray = $grouped->toArray();

        $this->assertArrayHasKey('NYC', $groupedArray);
        $this->assertArrayHasKey('LA', $groupedArray);
        $this->assertSame(2, $groupedArray['NYC']->count());
        $this->assertSame(1, $groupedArray['LA']->count());
    }

    public function testChunk(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $chunks = $collection->chunk(2);

        $this->assertSame(3, $chunks->count());
    }

    public function testReduce(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $sum = $collection->reduce(fn(int $carry, int $item): int => $carry + $item, 0);

        $this->assertSame(15, $sum);
    }

    public function testSortByColumn(): void
    {
        $collection = new Collection([
            ['name' => 'Charlie', 'age' => 35],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ]);

        $sorted = $collection->sortBy('name');
        $names = $sorted->pluck('name')->toArray();

        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function testSortByColumnDesc(): void
    {
        $collection = new Collection([
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Charlie', 'age' => 35],
            ['name' => 'Bob', 'age' => 30],
        ]);

        $sorted = $collection->sortBy('age', 'desc');
        $ages = $sorted->pluck('age')->toArray();

        $this->assertSame([35, 30, 25], $ages);
    }

    public function testUnique(): void
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3]);
        $unique = $collection->unique();

        $this->assertSame([1, 2, 3], $unique->toArray());
    }

    public function testUniqueByKey(): void
    {
        $collection = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 1, 'name' => 'Alice Duplicate'],
        ]);

        $unique = $collection->unique('id');

        $this->assertSame(2, $unique->count());
    }

    public function testContains(): void
    {
        $collection = new Collection([
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ]);

        $this->assertTrue($collection->contains(fn(array $item): bool => $item['name'] === 'Alice'));
        $this->assertFalse($collection->contains(fn(array $item): bool => $item['name'] === 'Charlie'));
    }

    public function testToJson(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $this->assertSame('["a","b","c"]', $collection->toJson());
    }

    public function testJsonSerializable(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertSame('[1,2,3]', json_encode($collection));
    }

    public function testCountable(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertCount(3, $collection);
    }

    public function testIterable(): void
    {
        $collection = new Collection([10, 20, 30]);
        $sum = 0;

        foreach ($collection as $item) {
            $sum += $item;
        }

        $this->assertSame(60, $sum);
    }

    public function testArrayAccess(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $this->assertTrue(isset($collection[0]));
        $this->assertSame('a', $collection[0]);
        $this->assertSame('c', $collection[2]);
        $this->assertNull($collection[99]);
    }

    public function testEach(): void
    {
        $collection = new Collection([1, 2, 3]);
        $sum = 0;

        $result = $collection->each(function (int $item) use (&$sum): void {
            $sum += $item;
        });

        $this->assertSame(6, $sum);
        $this->assertSame($collection, $result); // Returns self for chaining
    }
}

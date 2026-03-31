<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Sniper\Utils\EventFilter;

/**
 * Unit tests for the EventFilter utility.
 */
final class EventFilterTest extends TestCase
{
    public function testExpiredFiltersExpiredEvents(): void
    {
        $events = [
            ['title' => 'Past Event', 'date' => '2020-01-01'],
            ['title' => 'Future Event', 'date' => '2099-12-31'],
        ];

        $expired = EventFilter::expired($events, 'date');

        $this->assertCount(1, $expired);
        $this->assertSame('Past Event', $expired[0]['title']);
    }

    public function testUpcomingFiltersUpcomingEvents(): void
    {
        $events = [
            ['title' => 'Past Event', 'date' => '2020-01-01'],
            ['title' => 'Future Event', 'date' => '2099-12-31'],
        ];

        $upcoming = EventFilter::upcoming($events, 'date');

        $this->assertCount(1, $upcoming);
        $this->assertSame('Future Event', $upcoming[0]['title']);
    }

    public function testUpcomingWithTimeColumn(): void
    {
        $events = [
            ['title' => 'A', 'date' => '2099-06-15', 'time' => '09:00:00'],
            ['title' => 'B', 'date' => '2020-06-15', 'time' => '09:00:00'],
        ];

        $upcoming = EventFilter::upcoming($events, 'date', 'time');

        $this->assertCount(1, $upcoming);
        $this->assertSame('A', $upcoming[0]['title']);
    }

    public function testNextReturnsClosestFutureEvent(): void
    {
        $events = [
            ['title' => 'Far Future', 'date' => '2099-12-31'],
            ['title' => 'Near Future', 'date' => '2098-06-15'],
            ['title' => 'Past', 'date' => '2020-01-01'],
        ];

        $next = EventFilter::next($events, 'date');

        $this->assertNotNull($next);
        $this->assertSame('Near Future', $next['title']);
    }

    public function testNextReturnsNullWhenNoUpcoming(): void
    {
        $events = [
            ['title' => 'Past Event', 'date' => '2020-01-01'],
        ];

        $next = EventFilter::next($events, 'date');

        $this->assertNull($next);
    }

    public function testExpiredReturnsEmptyArrayWhenNone(): void
    {
        $events = [
            ['title' => 'Future', 'date' => '2099-12-31'],
        ];

        $expired = EventFilter::expired($events, 'date');

        $this->assertEmpty($expired);
    }
}

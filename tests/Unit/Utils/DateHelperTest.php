<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Sniper\Utils\DateHelper;

/**
 * Unit tests for the DateHelper utility.
 */
final class DateHelperTest extends TestCase
{
    public function testDaysBetweenStrings(): void
    {
        $days = DateHelper::daysBetween('2024-01-01', '2024-01-15');

        $this->assertSame(14, $days);
    }

    public function testDaysBetweenTimestamps(): void
    {
        $date1 = strtotime('2024-01-01');
        $date2 = strtotime('2024-01-11');

        $days = DateHelper::daysBetween($date1, $date2);

        $this->assertSame(10, $days);
    }

    public function testDaysBetweenSameDate(): void
    {
        $this->assertSame(0, DateHelper::daysBetween('2024-06-15', '2024-06-15'));
    }

    public function testIsPast(): void
    {
        $this->assertTrue(DateHelper::isPast('2020-01-01'));
    }

    public function testIsFuture(): void
    {
        $this->assertTrue(DateHelper::isFuture('2099-12-31'));
    }

    public function testDiffForHumans(): void
    {
        $result = DateHelper::diffForHumans('2024-01-01', '2024-04-01');

        $this->assertSame('3 months', $result);
    }

    public function testDiffForHumansDays(): void
    {
        $result = DateHelper::diffForHumans('2024-01-01', '2024-01-06');

        $this->assertSame('5 days', $result);
    }

    public function testDiffForHumansSingle(): void
    {
        $result = DateHelper::diffForHumans('2024-01-01', '2024-01-02');

        $this->assertSame('1 day', $result);
    }

    public function testDiffForHumansYears(): void
    {
        $result = DateHelper::diffForHumans('2020-01-01', '2023-01-01');

        $this->assertSame('3 years', $result);
    }
}

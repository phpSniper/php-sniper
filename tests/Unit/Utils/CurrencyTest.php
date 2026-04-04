<?php

declare(strict_types=1);

namespace Sniper\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Sniper\Utils\Currency;

/**
 * Unit tests for the Currency utility.
 */
final class CurrencyTest extends TestCase
{
    public function testFormatPlainNumber(): void
    {
        $result = Currency::format(1234567.89);

        $this->assertSame('1,234,567.89', $result);
    }

    public function testFormatCompactThousands(): void
    {
        $result = Currency::formatCompact(1500);

        $this->assertSame('1.5K', $result);
    }

    public function testFormatCompactMillions(): void
    {
        $result = Currency::formatCompact(1234567);

        $this->assertSame('1.23M', $result);
    }

    public function testFormatCompactBillions(): void
    {
        $result = Currency::formatCompact(5600000000);

        $this->assertSame('5.6B', $result);
    }

    public function testFormatCompactTrillions(): void
    {
        $result = Currency::formatCompact(1200000000000);

        $this->assertSame('1.2T', $result);
    }

    public function testFormatCompactSmallNumber(): void
    {
        $result = Currency::formatCompact(500);

        $this->assertSame('500.00', $result);
    }

    public function testFormatCompactWithCurrencySymbol(): void
    {
        $result = Currency::formatCompact(2500000, 1, '$');

        $this->assertSame('$2.5M', $result);
    }

    public function testFormatCompactNegative(): void
    {
        $result = Currency::formatCompact(-1500, 1);

        $this->assertSame('-1.5K', $result);
    }

    public function testParseSimpleNumber(): void
    {
        $result = Currency::parse('1,234,567.89');

        $this->assertSame(1234567.89, $result);
    }

    public function testParseWithCurrencySymbol(): void
    {
        $result = Currency::parse('$1,234.56');

        $this->assertSame(1234.56, $result);
    }

    public function testParseEuropeanFormat(): void
    {
        $result = Currency::parse('1.234.567,89');

        $this->assertSame(1234567.89, $result);
    }

    public function testParseEmptyString(): void
    {
        $result = Currency::parse('');

        $this->assertSame(0.0, $result);
    }
}

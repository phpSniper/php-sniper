<?php

declare(strict_types=1);

namespace Sniper\Utils;

use NumberFormatter;

/**
 * Currency formatting utilities.
 *
 * Provides locale-aware currency formatting using PHP's `intl` extension
 * when available, with a graceful fallback to `number_format()`.
 *
 * @example
 * ```php
 * use Sniper\Utils\Currency;
 *
 * echo Currency::format(1234567.89);                      // "1,234,567.89"
 * echo Currency::format(1234567.89, 'USD');               // "$1,234,567.89"
 * echo Currency::format(1234567.89, 'EUR', 'de_DE');      // "1.234.567,89 €"
 * echo Currency::format(1234567.89, 'NGN');               // "₦1,234,567.89"
 * echo Currency::formatCompact(1234567);                  // "1.23M"
 * ```
 */
final class Currency
{
    /**
     * Format a number as currency.
     *
     * Uses the `intl` extension for locale-aware formatting when available.
     * Falls back to `number_format()` with manual symbol prefixing.
     *
     * @param float|int|string $amount The monetary amount.
     * @param string|null $currencyCode ISO 4217 currency code (e.g., 'USD', 'EUR', 'NGN'). Null for plain number formatting.
     * @param string $locale The locale for formatting (e.g., 'en_US', 'de_DE').
     * @param int $decimals Number of decimal places.
     * @return string The formatted currency string.
     */
    public static function format(
        float|int|string $amount,
        ?string $currencyCode = null,
        string $locale = 'en_US',
        int $decimals = 2,
    ): string {
        $amount = (float) $amount;

        // Use intl extension if available and a currency code is provided
        if ($currencyCode !== null && class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $result = $formatter->formatCurrency($amount, strtoupper($currencyCode));

            return $result !== false ? $result : self::fallbackFormat($amount, $currencyCode, $decimals);
        }

        // Plain number format or fallback
        return $currencyCode !== null
            ? self::fallbackFormat($amount, $currencyCode, $decimals)
            : number_format($amount, $decimals);
    }

    /**
     * Format a number in compact notation (K, M, B, T).
     *
     * @param float|int|string $amount The number to format.
     * @param int $precision Decimal precision.
     * @param string|null $currencySymbol Optional symbol to prepend (e.g., '$').
     * @return string The compact formatted string (e.g., "1.23M", "$5.6B").
     */
    public static function formatCompact(
        float|int|string $amount,
        int $precision = 2,
        ?string $currencySymbol = null,
    ): string {
        $amount = (float) $amount;
        $prefix = $currencySymbol ?? '';
        $isNegative = $amount < 0;
        $amount = abs($amount);

        $suffixes = [
            ['threshold' => 1_000_000_000_000, 'suffix' => 'T'],
            ['threshold' => 1_000_000_000, 'suffix' => 'B'],
            ['threshold' => 1_000_000, 'suffix' => 'M'],
            ['threshold' => 1_000, 'suffix' => 'K'],
        ];

        foreach ($suffixes as $tier) {
            if ($amount >= $tier['threshold']) {
                $formatted = round($amount / $tier['threshold'], $precision);
                $sign = $isNegative ? '-' : '';

                return "{$sign}{$prefix}{$formatted}{$tier['suffix']}";
            }
        }

        $sign = $isNegative ? '-' : '';

        return "{$sign}{$prefix}" . number_format($amount, $precision);
    }

    /**
     * Parse a formatted currency string back into a float.
     *
     * Strips common currency symbols, thousands separators, and whitespace.
     *
     * @param string $formatted The formatted currency string.
     * @return float The parsed numeric value.
     */
    public static function parse(string $formatted): float
    {
        // Remove common currency symbols and whitespace
        $cleaned = preg_replace('/[^\d.\-,]/', '', $formatted);
        if ($cleaned === null || $cleaned === '') {
            return 0.0;
        }

        // Handle European format (1.234.567,89 → 1234567.89)
        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $lastComma = strrpos($cleaned, ',');
            $lastDot = strrpos($cleaned, '.');

            if ($lastComma > $lastDot) {
                // European: dots are thousands, comma is decimal
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // US: commas are thousands, dot is decimal
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif (str_contains($cleaned, ',')) {
            // Could be thousands separator or decimal — check position
            $commaPos = strrpos($cleaned, ',');
            $afterComma = strlen($cleaned) - $commaPos - 1;

            if ($afterComma <= 2) {
                // Likely a decimal separator
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // Likely a thousands separator
                $cleaned = str_replace(',', '', $cleaned);
            }
        }

        return (float) $cleaned;
    }

    /**
     * Fallback currency formatting without the intl extension.
     *
     * @param float $amount The amount.
     * @param string $currencyCode The currency code.
     * @param int $decimals Decimal places.
     * @return string The formatted string.
     */
    private static function fallbackFormat(float $amount, string $currencyCode, int $decimals): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'NGN' => '₦',
            'INR' => '₹',
            'BRL' => 'R$',
            'CAD' => 'CA$',
            'AUD' => 'A$',
            'CNY' => '¥',
            'KRW' => '₩',
            'RUB' => '₽',
            'TRY' => '₺',
            'ZAR' => 'R',
        ];

        $symbol = $symbols[strtoupper($currencyCode)] ?? strtoupper($currencyCode) . ' ';

        return $symbol . number_format($amount, $decimals);
    }
}

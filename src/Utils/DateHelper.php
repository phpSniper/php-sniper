<?php

declare(strict_types=1);

namespace Sniper\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Date and time helper utilities.
 *
 * Provides modern, `DateTimeImmutable`-based date operations as a
 * replacement for the legacy `strtotime`-based functions.
 *
 * @example
 * ```php
 * use Sniper\Utils\DateHelper;
 *
 * $days = DateHelper::daysBetween('2024-01-01', '2024-03-15');  // 74
 * $isToday = DateHelper::isToday('2024-03-15');                 // true/false
 * $isPast = DateHelper::isPast('2023-12-25');                   // true
 * ```
 */
final class DateHelper
{
    /**
     * Calculate the number of days between two dates.
     *
     * Accepts any format parsable by `DateTimeImmutable`, Unix timestamps
     * (as integers), or existing `DateTimeInterface` instances.
     *
     * @param string|int|DateTimeInterface $date1 The first date.
     * @param string|int|DateTimeInterface $date2 The second date.
     * @return int The absolute number of days between the two dates.
     */
    public static function daysBetween(
        string|int|DateTimeInterface $date1,
        string|int|DateTimeInterface $date2,
    ): int {
        $dateTime1 = self::toDateTime($date1);
        $dateTime2 = self::toDateTime($date2);

        $interval = $dateTime1->diff($dateTime2);

        return $interval->days !== false ? $interval->days : 0;
    }

    /**
     * Check if a date is today.
     *
     * @param string|int|DateTimeInterface $date The date to check.
     * @param DateTimeZone|null $timezone Optional timezone for "today" evaluation.
     * @return bool True if the date falls on today.
     */
    public static function isToday(
        string|int|DateTimeInterface $date,
        ?DateTimeZone $timezone = null,
    ): bool {
        $dateTime = self::toDateTime($date);
        $now = new DateTimeImmutable('now', $timezone);

        return $dateTime->format('Y-m-d') === $now->format('Y-m-d');
    }

    /**
     * Check if a date is in the past.
     *
     * @param string|int|DateTimeInterface $date The date to check.
     * @return bool True if the date is before now.
     */
    public static function isPast(string|int|DateTimeInterface $date): bool
    {
        return self::toDateTime($date) < new DateTimeImmutable();
    }

    /**
     * Check if a date is in the future.
     *
     * @param string|int|DateTimeInterface $date The date to check.
     * @return bool True if the date is after now.
     */
    public static function isFuture(string|int|DateTimeInterface $date): bool
    {
        return self::toDateTime($date) > new DateTimeImmutable();
    }

    /**
     * Get the difference between two dates as a human-readable string.
     *
     * @param string|int|DateTimeInterface $date1 The first date.
     * @param string|int|DateTimeInterface $date2 The second date.
     * @return string Human-readable difference (e.g., "2 days", "3 months").
     */
    public static function diffForHumans(
        string|int|DateTimeInterface $date1,
        string|int|DateTimeInterface $date2,
    ): string {
        $interval = self::toDateTime($date1)->diff(self::toDateTime($date2));

        if ($interval->y > 0) {
            return $interval->y === 1 ? '1 year' : "{$interval->y} years";
        }
        if ($interval->m > 0) {
            return $interval->m === 1 ? '1 month' : "{$interval->m} months";
        }
        if ($interval->d > 0) {
            return $interval->d === 1 ? '1 day' : "{$interval->d} days";
        }
        if ($interval->h > 0) {
            return $interval->h === 1 ? '1 hour' : "{$interval->h} hours";
        }
        if ($interval->i > 0) {
            return $interval->i === 1 ? '1 minute' : "{$interval->i} minutes";
        }

        return 'just now';
    }

    /**
     * Convert a flexible date input to a DateTimeImmutable instance.
     *
     * @param string|int|DateTimeInterface $date The date input.
     * @return DateTimeImmutable The parsed date.
     */
    private static function toDateTime(string|int|DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        if (is_int($date)) {
            return (new DateTimeImmutable())->setTimestamp($date);
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date);
        if ($parsed !== false) {
            return $parsed;
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($parsed !== false) {
            return $parsed->setTime(0, 0);
        }

        // Fallback: let PHP parse it
        return new DateTimeImmutable($date);
    }
}

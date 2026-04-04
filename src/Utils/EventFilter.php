<?php

declare(strict_types=1);

namespace Sniper\Utils;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Event filtering utilities.
 *
 * Provides static methods to filter arrays of events by date/time columns.
 * Works with any array of associative arrays that contain date fields.
 *
 * @example
 * ```php
 * use Sniper\Utils\EventFilter;
 *
 * $events = [
 *     ['title' => 'Conference', 'date' => '2024-12-15', 'time' => '09:00'],
 *     ['title' => 'Meeting',    'date' => '2024-01-10', 'time' => '14:00'],
 * ];
 *
 * $upcoming = EventFilter::upcoming($events, 'date', 'time');
 * $expired  = EventFilter::expired($events, 'date', 'time');
 * $next     = EventFilter::next($events, 'date', 'time');
 * $today    = EventFilter::today($events, 'date');
 * ```
 */
final class EventFilter
{
    /**
     * Get all events that are in the past (expired).
     *
     * @param array<int, array<string, mixed>> $events The event data.
     * @param string $dateColumn The key containing the date.
     * @param string|null $timeColumn Optional key containing the time.
     * @return array<int, array<string, mixed>> Expired events.
     */
    public static function expired(array $events, string $dateColumn, ?string $timeColumn = null): array
    {
        $now = new DateTimeImmutable();

        return array_values(array_filter($events, function (array $event) use ($dateColumn, $timeColumn, $now): bool {
            $eventDateTime = self::parseEventDateTime($event, $dateColumn, $timeColumn);

            return $eventDateTime < $now;
        }));
    }

    /**
     * Get all events that are in the future (upcoming).
     *
     * @param array<int, array<string, mixed>> $events The event data.
     * @param string $dateColumn The key containing the date.
     * @param string|null $timeColumn Optional key containing the time.
     * @return array<int, array<string, mixed>> Upcoming events.
     */
    public static function upcoming(array $events, string $dateColumn, ?string $timeColumn = null): array
    {
        $now = new DateTimeImmutable();

        return array_values(array_filter($events, function (array $event) use ($dateColumn, $timeColumn, $now): bool {
            $eventDateTime = self::parseEventDateTime($event, $dateColumn, $timeColumn);

            return $eventDateTime > $now;
        }));
    }

    /**
     * Get all events happening today.
     *
     * @param array<int, array<string, mixed>> $events The event data.
     * @param string $dateColumn The key containing the date.
     * @return array<int, array<string, mixed>> Today's events.
     */
    public static function today(array $events, string $dateColumn): array
    {
        $today = (new DateTimeImmutable())->format('Y-m-d');

        return array_values(array_filter($events, function (array $event) use ($dateColumn, $today): bool {
            $eventDate = (string) ($event[$dateColumn] ?? '');
            $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $eventDate);

            if ($parsed === false) {
                $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $eventDate);
            }

            if ($parsed === false) {
                return false;
            }

            return $parsed->format('Y-m-d') === $today;
        }));
    }

    /**
     * Get the next upcoming event (the soonest future event).
     *
     * @param array<int, array<string, mixed>> $events The event data.
     * @param string $dateColumn The key containing the date.
     * @param string|null $timeColumn Optional key containing the time.
     * @return array<string, mixed>|null The next event, or null if none are upcoming.
     */
    public static function next(array $events, string $dateColumn, ?string $timeColumn = null): ?array
    {
        $upcoming = self::upcoming($events, $dateColumn, $timeColumn);

        if (empty($upcoming)) {
            return null;
        }

        // Sort by datetime ascending and return the first (soonest)
        usort($upcoming, function (array $a, array $b) use ($dateColumn, $timeColumn): int {
            $dateTimeA = self::parseEventDateTime($a, $dateColumn, $timeColumn);
            $dateTimeB = self::parseEventDateTime($b, $dateColumn, $timeColumn);

            return $dateTimeA <=> $dateTimeB;
        });

        return $upcoming[0];
    }

    /**
     * Parse a datetime from an event's date and optional time columns.
     *
     * @param array<string, mixed> $event The event data.
     * @param string $dateColumn The date key.
     * @param string|null $timeColumn Optional time key.
     * @return DateTimeImmutable The parsed datetime.
     */
    private static function parseEventDateTime(
        array $event,
        string $dateColumn,
        ?string $timeColumn,
    ): DateTimeImmutable {
        $dateString = (string) ($event[$dateColumn] ?? '');

        if ($timeColumn !== null && isset($event[$timeColumn])) {
            $dateString .= ' ' . (string) $event[$timeColumn];
        }

        return new DateTimeImmutable($dateString);
    }
}

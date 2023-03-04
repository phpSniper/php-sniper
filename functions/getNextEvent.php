<?php


/**
 * Get the next upcoming event from a list of events based on a date column.
 *
 * @param array $events The list of events.
 * @param string $dateColumn The name of the date column in the events array.
 * @param string|null $timeColumn The name of the time column in the events array (optional).
 *
 * @return mixed|null The next upcoming event if one is found, null otherwise.
 */
function getNextEvent(array $events, string $dateColumn, string $timeColumn = null)
{
    $now = time();
    $nextEvent = null;

    foreach ($events as $event) {
        $dateTimeString = $timeColumn ? "{$event[$dateColumn]} {$event[$timeColumn]}" : $event[$dateColumn];
        $eventTime = strtotime($dateTimeString);

        if ($eventTime > $now && (!$nextEvent || $eventTime < $nextEvent['time'])) {
            $nextEvent = ["event" => $event, "time" => $eventTime];
        }
    }

    return $nextEvent ? $nextEvent['event'] : null;
}




?>
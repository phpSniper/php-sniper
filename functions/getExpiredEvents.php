<?php


/**
 * Returns an array of events from the provided data that have expired based on the provided date column.
 *
 * @param array $data An array of events to filter.
 * @param string $dateColumn The column containing the event date.
 *
 * @return array|integer An array of expired events, or 0 if there are no expired events.
 */
function getExpiredEvents($data, $dateColumn)
{
    $filteredEvents = [];
    foreach ($data as $event) {
        if (strtotime($event[$dateColumn]) < time()) {
            array_push($filteredEvents, $event);
        }
    }

    if (count($filteredEvents) === 0) {
        return 0;
    }
    return $filteredEvents;
}



?>
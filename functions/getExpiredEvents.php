<?php


/**
 * Returns an array of events from the provided data that have expired based on the provided date column.
 *
 * @param array $data An array of events to filter.
 * @param string $dateColumn The column containing the event date.
 * @param string $timeColumn the column containg the event time its optional.
 *
 * @return array|integer An array of expired events, or 0 if there are no expired events.
 */
function getExpiredEvents($data, $dateColumn, $timeColumn = null)
{
    $filteredEvents = [];
    foreach ($data as $event) {
        $dateTime = $event[$dateColumn];
        if ($timeColumn !== null) {
            $dateTime .= ' ' . $event[$timeColumn];
        }

        if (strtotime($dateTime) < time()) {
            array_push($filteredEvents, $event);
        }
    }

    if (count($filteredEvents) === 0) {
        return 0;
    }
    return $filteredEvents;
}


?>
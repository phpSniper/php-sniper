<?php


/**
 * Returns an array of upcoming events from a dataset based on the date column
 *
 * @param array $data The dataset to search through
 * @param string $dateColumn The name of the date column to check against
 * @param string $timeColumn The name of the time column to check against
 *
 * @return mixed An array of upcoming events or 0 if no upcoming events found
 */
function filterUpcomingEvents(array $data, string $dateColumn, string $timeColumn) {
    $filteredEvents = [];
    foreach ($data as $event) {
        $dateTime = $event[$dateColumn];
        if ($timeColumn !== null) {
            $dateTime .= ' ' . $event[$timeColumn];
        }

        if (strtotime($dateTime) > time()) {
            array_push($filteredEvents, $event);
        }
    }

    return $filteredEvents;
}

?>
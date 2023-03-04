<?php


/**
 * Returns an array of upcoming events from a dataset based on the date column
 *
 * @param array $data The dataset to search through
 * @param string $dateColumn The name of the date column to check against
 *
 * @return mixed An array of upcoming events or 0 if no upcoming events found
 */
function filterUpcomingEvents(array $data, string $dateColumn) {
    $upcomingEvents = [];

    foreach ($data as $event) {
        if (strtotime($event[$dateColumn]) > time()) {
            $upcomingEvents[] = $event;
        }
    }

    return count($upcomingEvents) > 0 ? $upcomingEvents : 0;
}

?>
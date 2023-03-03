<?php


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
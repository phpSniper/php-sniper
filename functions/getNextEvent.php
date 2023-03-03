<?php


function getNextEvent($events, $dateColumn, $timeColumn = null)
{

    // Get the current time in seconds 
    $now = time();

    // Initialize the next event to null 
    $nextEvent = null;

    // Loop through the events array 
    foreach ($events as $event) {



        // Convert each date to seconds 
        if ($timeColumn != null) {
            // merge the date and time columns
            $dateAndTime = $event[$dateColumn] . " " .  $event[$timeColumn];
            // convert  date and time to seconds
            $dateInSeconds = strtotime($dateAndTime);
        } else {

            $dateInSeconds = strtotime($event[$dateColumn]);
        }

        // Check if this date is greater than current time and smaller than the next event date (if set) 
        if ($dateInSeconds > $now && ($nextEvent == null || $dateInSeconds < $nextEvent)) {

            // Set this event as the next event if it meets the criteria  
            $nextEvent = $dateInSeconds;

            // Store the key of this event (to get its name later)  
            $nextEventOnly = $event;
        }
    }

    // Check if we found a next event or not  
    if ($nextEvent != null) {

        // Print the name of the next event  
        return $nextEventOnly;
    } else {

        return null;
    }
}



?>
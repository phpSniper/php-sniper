<?php


/**
 * Calculate the number of days between two dates.
 *
 * @param mixed $date1 The first date to compare.
 * @param mixed $date2 The second date to compare.
 *
 * @return int The number of days between the two dates.
 */
function getDaysBetweenDates($date1, $date2)
{
    // Check if the dates are in seconds
    if (!is_numeric($date1)) {
        $timestamp1 = strtotime($date1);
    } else {
        $timestamp1 = $date1;
    }
    if (!is_numeric($date2)) {
        $timestamp2 = strtotime($date2);
    } else {
        $timestamp2 = $date2;
    }
    // Calculate the difference in seconds
    $difference = abs($timestamp2 - $timestamp1);

    // Calculate the number of days by dividing the difference in seconds by the number of seconds in a day
    $days = $difference / 86400;

    // Return the number of days
    return (int)$days;
}


?>
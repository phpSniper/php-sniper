<?php

/**
 * Returns an array of items from given data array that have a date in $dateColumn that matches today's date.
 * 
 * @param array $data The array of data to search through.
 * @param string $dateColumn The name of the column in $data that holds the dates to check.
 * @return array An array containing two keys: "data" (an array of matching items), and "num_rows" (the number of matching items).
 */
function getTodayItems($data, $dateColumn) {
    $todaysItems = array();
    foreach ($data as $item) {
        $date = strtotime($item[$dateColumn]);
        if (date('Y-m-d', $date) == date('Y-m-d')) {
            array_push($todaysItems, $item);
        }
    }
    return array("data" => $todaysItems, "num_rows" => count($todaysItems));
}


?>
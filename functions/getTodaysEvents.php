<?php


function getTodaysEvents($data, $dateColumn)
{
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
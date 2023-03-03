<?php

// get a complete row information

function getInfo($table, $condition, $cols = null)
{
    $conn = connect();
    $columns = $cols ? "(" . $cols . ")" : "*";
    $details = "SELECT " . $columns . " FROM $table WHERE " . $condition;
    $query = $conn->query($details);
    return $query->fetch_assoc();
}


?>
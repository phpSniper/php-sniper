<?php

function updateRow($table, $colsAndValues, $conditions)
{
    $conn = connect();
    $update = "UPDATE $table SET $colsAndValues WHERE $conditions ";
    $query = $conn->query($update);
    return $query ? true : false;
}


?>
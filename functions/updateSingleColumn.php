<?php


function updateCol($table, $col, $value, $key, $id)
{
    $conn = connect();
    $update = "UPDATE $table SET $col = '" . $value . "' WHERE $key = '" . $id . "'";
    $query = $conn->query($update);
    return $query ? true : false;
}


?>
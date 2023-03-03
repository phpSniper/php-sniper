<?php



// check if a particular value exist in a particular table

function existance($table, $condition)
{
    $conn = connect();
    $details = "SELECT * FROM $table WHERE $condition";
    $query = $conn->query($details);
    return $query->num_rows > 0 ? true : false;
}


?>
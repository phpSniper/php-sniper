<?php



// get row count
function counter($table, $condition = null)
{

$statement = '';

    if ($condition) {
        $statement = "WHERE " . $condition;
    }


    $conn = connect();
    $details = "SELECT * FROM $table  $statement";
    $query = $conn->query($details);
    return $query->num_rows;
}


?>
<?php


// insert data into table

function insert($table, $cols, $values)
{
    $conn = connect();
    $insertion = mysqli_query($conn, "INSERT INTO $table ($cols) VALUES ($values)");
    return $insertion == true ? true : false;
}





?>
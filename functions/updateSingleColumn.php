<?php


/**
 * Update a single column of a row in the specified table with the provided value, based on the provided key and id
 *
 * @param string $table The name of the table to update
 * @param string $col The name of the column to update
 * @param mixed $value The value to update the column with
 * @param string $key The name of the key to use in the WHERE clause
 * @param mixed $id The value of the key to use in the WHERE clause
 *
 * @return bool Returns true if the update was successful, false otherwise
 */
function updateSingleColumn($table, $col, $value, $key, $id)
{
    $conn = connect();
    $update = "UPDATE $table SET $col = '" . $value . "' WHERE $key = '" . $id . "'";
    $query = $conn->query($update);
    return $query ? true : false;
}

?>
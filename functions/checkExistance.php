<?php


/**
 * Checks if a row exists in a table based on the specified condition.
 *
 * @param string $table The name of the database table.
 * @param string $condition The WHERE condition for the query.
 *
 * @return bool True if the row exists, false otherwise.
 * @throws Exception If the query fails to execute.
 */
function rowExists($table, $condition)
{
    $conn = connect();
    $details = "SELECT * FROM $table WHERE $condition";
    $query = $conn->query($details);
    return $query->num_rows > 0 ? true : false;
}


?>
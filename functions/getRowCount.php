<?php

/**
 * Counts the number of rows in a database table based on the specified condition.
 *
 * @param string $table The name of the database table.
 * @param string|null $condition The WHERE condition for the query (optional).
 *
 * @return int The number of rows that match the specified condition.
 * @throws Exception If the query fails to execute.
 */
function countRows($table, $condition = null)
{
    $statement = '';

    if ($condition) {
        $statement = "WHERE " . $condition;
    }

    $conn = connect();
    $details = "SELECT * FROM $table $statement";
    $query = $conn->query($details);

    return $query->num_rows;
}

?>
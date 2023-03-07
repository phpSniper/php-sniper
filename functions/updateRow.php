<?php

/**
 * Updates rows in a database table that match certain conditions with new values.
 *
 * @param string $table The name of the database table to update rows in.
 * @param string $newValues The new column-value pairs to update in the format "col1=value1, col2=value2, ...".
 * @param string $conditions The conditions that rows must match to be updated in the format "col1=value1 AND col2=value2 AND ...".
 * @return bool True if the update query was successful, false otherwise.
 */
function updateRow(string $table, string $newValues, string $conditions): bool
{
    $conn = connect();
    $updateQuery = "UPDATE $table SET $newValues WHERE $conditions";
    $result = $conn->query($updateQuery);
    return $result !== false;
}

?>
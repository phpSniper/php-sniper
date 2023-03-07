<?php

// get a complete row information

/**
 * Retrieves information about a row in a table based on a specified condition
 *
 * @param string $tableName The name of the table to query
 * @param string $condition The condition to use in the WHERE clause of the query
 * @param string|null $columns A comma-separated list of columns to retrieve. If null, all columns are retrieved.
 *
 * @return array|null The associative array representing the row, or null if no matching row was found
 */
function selectRow(string $tableName, string $condition, string $columns = null): ?array
{
    $conn = connect();

    // Use the specified columns or retrieve all columns by default
    $selectedColumns = $columns ? "(" . $columns . ")" : "*";

    // Construct the query to retrieve the row information
    $query = "SELECT " . $selectedColumns . " FROM " . $tableName . " WHERE " . $condition;

    // Execute the query and retrieve the first (and only) row
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    // Return the row information, or null if no matching row was found
    return $row ? $row : null;
}


?>
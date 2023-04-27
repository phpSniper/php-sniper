<?php


/**
 * Fetch all rows from a table that match the specified condition.
 *
 * @param string $table The name of the table to fetch from.
 * @param string|null $condition The condition that rows must match (optional).
 * @param string|null $order_by The column to order the results by and the order method (optional).
 * @param string|null $cols The columns to select (optional).
 * @param bool $distinct Whether to select distinct rows (optional, default false).
 * @return array An array containing the fetched data and the number of rows.
 */
function selectAll($table, $condition = null, $order_by = null, $cols = null, $distinct = false)
{
    // Connect to the database
    $conn = connect();

    // Build the query
    if ($distinct) {
        // if distinct then no paranthesis
        $columns = $cols ? $cols : "*";
    } else {
        // if not distinct then add paranthesis 
        $columns = $cols ? "(" . $cols . ")" : "*";
    }

    $query = "SELECT " . $columns . " FROM " . $table;
    if ($condition) {
        $query .= " WHERE " . $condition;
    }

    if ($order_by) {
        $query .= " ORDER BY " . $order_by;
    }

    // Execute the query
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("query failed: " . mysqli_error($conn));
    }
    if (mysqli_num_rows($result) === 0) {
        return [];
    }

    // Fetch the data
    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    // count the number of rows in the data array
    $num_rows = count($data);

    // Close the connection
    mysqli_close($conn);

    // Return the data and the number of rows
    return $data;
}


?>
<?php



/**
 * Fetch all rows of data from a table based on the specified condition.
 *
 * @param string $table The name of the database table.
 * @param string|null $condition The WHERE condition for the query (optional).
 * @param string|null $cols The columns to select (optional).
 * @param bool $distinct Whether to use DISTINCT or not (default: false).
 * @param array $params An array of parameters to bind to the prepared statement (optional).
 *
 * @return array An array containing the data and the number of rows fetched.
 * @throws Exception If the query fails to execute.
 */
function fetchAll($table, $condition = null, $cols = null, $distinct = false, $params = [])
{
    // Connect to the database
    $conn = connect();

    // Build the query
    if ($distinct) {
        $columns = $cols ? $cols : "*";
    } else {
        $columns = $cols ? "(" . $cols . ")" : "*";
    }

    $query = "SELECT " . $columns . " FROM " . $table;
    if ($condition) {
        $query .= " WHERE " . $condition;
    }

    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . mysqli_error($conn));
    }

    if ($params) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Query execution failed: " . mysqli_error($conn));
    }

    // Fetch the data
    $data = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

    // Close the connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    // Return the data and the number of rows
    return array("data" => $data, "num_rows" => count($data));
}

?>
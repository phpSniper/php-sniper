<?php



/**
 * Deletes a row from a table
 * 
 * @param string $table The name of the table to delete from
 * @param string $condition The condition to use in the WHERE clause
 * 
 * @return bool True if the delete was successful, false otherwise
 */
function delete($table, $condition)
{
    // Connect to the database
    $conn = connect();

    // Build the query
    $query = "DELETE FROM " . $table . " WHERE " . $condition;

    // Execute the query
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("query failed: " . mysqli_error($conn));
    }

    // Check if any rows were affected
    $num_rows = mysqli_affected_rows($conn);

    // Close the connection
    mysqli_close($conn);

    // Return true if any rows were affected, false otherwise
    return $num_rows > 0;
}



?>
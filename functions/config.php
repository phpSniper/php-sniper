<?php

/**
 * Establishes a connection to the database.
 *
 * @return mysqli The database connection object.
 */
function connect()
{
    // Replace these credentials with your own
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "donya";

    // Create a connection object
    $conn = mysqli_connect($host, $user, $pass, $db);

    // Check if the connection was successful
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Return the connection object
    return $conn;
}

?>
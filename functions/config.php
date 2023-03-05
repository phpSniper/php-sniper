<?php

/**
 * Establishes a connection to the database.
 *
 * @return mysqli The database connection object.
 */
function connect()
{
    // Replace these credentials with your own
    $host = "Your_host_name";
    $user = "Your_username";
    $pass = "Your_password";
    $db = "your_database_name";

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
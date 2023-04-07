<?php

function customSelection($query)
{
    $conn = connect();

    // Execute the query
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    return $row ? $row : null;
}


?>
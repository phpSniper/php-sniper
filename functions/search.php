<?php

/**
 * Check if all characters in the query string are found in the data string.
 *
 * @param string $data The string to search within.
 * @param string $query The query string to search for.
 * @return bool True if all query characters are found in the data string, false otherwise.
 */
function search($data, $query)
{
    $char = str_split($query);
    $length = sizeof($char) - 1;

    $n = 0;
    foreach ($char as $c) {
        if (strpos($data, $c) !== false) $n++;
    }
    return $n >= $length ? true : false;
}



?>
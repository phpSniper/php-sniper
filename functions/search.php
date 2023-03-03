<?php


// search funcion 
function search($data, $query)
{
    $char = str_split($query);
    $lenght = sizeof($char) - 1;

    $n = 0;
    foreach ($char as $c) {
        if (strpos($data, $c) !== false) $n++;
    }
    return $n >= $lenght ? true : false;
}




?>
<?php

function prepareColumnsForInsert($columns) {
    $columnStr = implode(",", $columns);
    $columnStr = "'".str_replace(",", "','", $columnStr)."'";
    return $columnStr;
    
  }

/**
 * Insert data into a database table
 *
 * @param string $table The name of the table to insert data into
 * @param string $cols A comma-separated string of column names
 * @param string $values A comma-separated string of values to insert into the corresponding columns
 *
 * @return bool Returns true if data is inserted successfully, false otherwise
 */
   
function insert($table, $cols, $values)
{
    // Connect to the database
    $conn = connect();

      // check if its an array type to arrange correctly
      if (is_array($values)) {
        $values = prepareColumnsForInsert($values);
    }

    // Construct the insertion query
    $query = "INSERT INTO $table ($cols) VALUES ($values)";

    // Execute the insertion query
    $insertion = mysqli_query($conn, $query);

    // Return true if data is inserted successfully, false otherwise
    return $insertion == true ? true : false;
}



?>
# phpSniper



phpSniper is a PHP library that provides a collection of functions to simplify database operations such as CRUD (Create, Read, Update, and Delete) and other common tasks in PHP web development.

## Requirements

- PHP 5.4 or higher
- MySQL 5.0 or higher

## Installation

1. Clone the phpSniper repository or download it as a zip file and extract it in your project folder.
2. Open the `functions/config.php` file and enter your MySQL database credentials.
3. Include the `sniper.php` file in your PHP script to start using the library.

## Usage

Here's an example of how to use the `select` function to retrieve all rows from a database table:

```php
include 'path/to/sniper.php';

$rows = select('users');

foreach ($rows as $row) {
    echo $row['name'];
}
```


## You can also use the `insert` function to insert data into a table:

```php
include 'path/to/sniper.php';

$table = 'users';
$columns = 'name, email';
$values = "'John Doe', 'johndoe@example.com'";

$inserted = insert($table, $columns, $values);

if ($inserted) {
    echo "Data inserted successfully!";
} else {
    echo "Failed to insert data!";
}

```

## Function List

- `connect()` - Establish a database connection
- `closeConnection()` - Close the database connection
- `insert($table, $cols, $values)` - Insert data into a database table
- `updateRow($table, $colsAndValues, $conditions)` - Update multiple columns and values in a database table
- `updateCol($table, $col, $value, $key, $id)` - Update a single column value in a database table
- `delete($table, $condition)` - Delete row(s) from a database table based on a condition
- `search($data, $query)` - Search for a string in data
- `getInfo($table, $condition, $cols = null)` - Retrieve complete row information from a database table.


---

**phpSniper** is open source software released under the [MIT license](https://opensource.org/licenses/MIT).

© [PHP Sniper](https://web-php-sniper.vercel.app)

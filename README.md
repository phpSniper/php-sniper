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
$inserted = insert('users', 'name, email', "'John Doe', 'johndoe@example.com'");
if ($inserted) {
    echo "Data inserted successfully!";
} else {
    echo "Failed to insert data!";
}

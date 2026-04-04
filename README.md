<p align="center">
  <h1 align="center">⚡ phpSniper</h1>
  <p align="center">
    <strong>A lightweight, secure, and fluent PHP database toolkit.</strong>
    <br />
    Simplify your database interactions with an elegant query builder.
  </p>
  <p align="center">
    <a href="https://github.com/phpSniper/php-sniper/actions"><img src="https://github.com/phpSniper/php-sniper/workflows/CI/badge.svg" alt="CI Status"></a>
    <a href="https://packagist.org/packages/phpsniper/sniper"><img src="https://img.shields.io/packagist/v/phpsniper/sniper.svg" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/phpsniper/sniper"><img src="https://img.shields.io/packagist/php-v/phpsniper/sniper.svg" alt="PHP Version"></a>
    <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  </p>
</p>

---

## ✨ Why phpSniper?

| Feature | phpSniper | Raw PDO |
|:---|:---:|:---:|
| **SQL Injection Protection** | ✅ Automatic | ❌ Manual |
| **Fluent API** | ✅ Chainable | ❌ Verbose |
| **Zero Configuration** | ✅ Single call | ❌ DSN strings |
| **Pagination** | ✅ Built-in | ❌ Manual math |
| **Transactions** | ✅ One-liner | ❌ Try/catch |
| **Result Collections** | ✅ map/filter/pluck | ❌ Plain arrays |
| **Multi-Database** | ✅ MySQL, SQLite, PostgreSQL | ✅ Yes |

## 🚀 Quick Start

### Installation

```bash
composer require phpsniper/sniper
```

### Connect

```php
use Sniper\Sniper;

Sniper::connect([
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => 'secret',
]);
```

### Query

```php
// Select all users
$users = Sniper::table('users')->get();

// With conditions
$activeUsers = Sniper::table('users')
    ->select('name', 'email')
    ->where('active', true)
    ->where('age', '>=', 18)
    ->orderBy('name')
    ->limit(20)
    ->get();

// Get the first result
$user = Sniper::table('users')->where('id', 1)->first();

// Get a single value
$email = Sniper::table('users')->where('id', 1)->value('email');

// Pluck a column
$names = Sniper::table('users')->pluck('name');
```

### Insert

```php
$id = Sniper::table('users')->insert([
    'name'  => 'Jane Doe',
    'email' => 'jane@example.com',
]);

// Batch insert
Sniper::table('users')->insertBatch([
    ['name' => 'Alice', 'email' => 'alice@test.com'],
    ['name' => 'Bob',   'email' => 'bob@test.com'],
]);
```

### Update

```php
Sniper::table('users')
    ->where('id', 1)
    ->update(['name' => 'Updated Name']);
```

### Delete

```php
Sniper::table('users')->where('id', 1)->delete();
```

---

## 🔒 Security — Prepared Statements Everywhere

**Every query** in phpSniper uses PDO prepared statements. User values are **never** concatenated into SQL strings:

```php
// ✅ phpSniper — Safe. Values are parameterized automatically.
Sniper::table('users')->where('email', $userInput)->first();

// ❌ Raw PHP — Vulnerable to SQL injection!
// $query = "SELECT * FROM users WHERE email = '$userInput'";
```

---

## 📖 Full API Reference

### WHERE Clauses

```php
->where('column', 'value')              // WHERE column = value
->where('column', '>', 'value')         // WHERE column > value
->orWhere('column', 'value')            // OR WHERE column = value
->whereIn('column', [1, 2, 3])          // WHERE column IN (1, 2, 3)
->whereNotIn('column', [1, 2, 3])       // WHERE column NOT IN (1, 2, 3)
->whereNull('column')                   // WHERE column IS NULL
->whereNotNull('column')                // WHERE column IS NOT NULL
->whereBetween('column', 10, 100)       // WHERE column BETWEEN 10 AND 100
->whereRaw('age > ? AND score > ?', [18, 90])  // Raw SQL (use bindings!)
```

### JOINs

```php
->join('orders', 'users.id', '=', 'orders.user_id')
->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
->rightJoin('roles', 'users.role_id', '=', 'roles.id')
```

### Ordering, Grouping & Limits

```php
->orderBy('name')                       // ORDER BY name ASC
->orderBy('created_at', 'desc')         // ORDER BY created_at DESC
->latest()                              // ORDER BY created_at DESC
->oldest()                              // ORDER BY created_at ASC
->groupBy('status')                     // GROUP BY status
->having('COUNT(*) > ?', [5])           // HAVING COUNT(*) > 5
->limit(10)                             // LIMIT 10
->offset(20)                            // OFFSET 20
->take(10)->skip(20)                    // Aliases for limit/offset
```

### Aggregates

```php
$count = Sniper::table('users')->count();
$total = Sniper::table('orders')->where('status', 'paid')->sum('amount');
$avg   = Sniper::table('products')->avg('price');
$min   = Sniper::table('products')->min('price');
$max   = Sniper::table('products')->max('price');
```

### Existence Checks

```php
if (Sniper::table('users')->where('email', $email)->exists()) {
    // User with this email already exists
}
```

### Pagination

```php
$page = Sniper::table('users')
    ->orderBy('name')
    ->paginate(perPage: 20, currentPage: 1);

$page->items();        // Collection of users
$page->total();        // Total user count
$page->currentPage();  // 1
$page->lastPage();     // 5
$page->hasMorePages(); // true
$page->toJson();       // JSON for API responses
```

### Transactions

```php
Sniper::transaction(function () {
    Sniper::table('accounts')->where('id', 1)->update(['balance' => 900]);
    Sniper::table('accounts')->where('id', 2)->update(['balance' => 1100]);
});
// Automatically commits on success, rolls back on exception
```

### Raw Queries

```php
// When you need full SQL control
$results = Sniper::raw('SELECT * FROM users WHERE id = ?', [1]);

// DDL / non-select statements
Sniper::statement('ALTER TABLE users ADD COLUMN avatar TEXT');
```

### Collections

Query results are wrapped in `Collection` objects with functional methods:

```php
$users = Sniper::table('users')->get();

$users->map(fn($u) => $u['name']);
$users->filter(fn($u) => $u['age'] > 25);
$users->pluck('email');
$users->sortBy('name');
$users->groupBy('role');
$users->chunk(10);
$users->count();
$users->isEmpty();
$users->first();
$users->last();
$users->toArray();
$users->toJson();
```

### Debugging

```php
$compiled = Sniper::table('users')->where('active', 1)->toSql();
// ['sql' => 'SELECT * FROM `users` WHERE `active` = ?', 'bindings' => [1]]

$raw = Sniper::table('users')->where('active', 1)->toRawSql();
// "SELECT * FROM `users` WHERE `active` = 1"  (for debugging only!)
```

---

## 🛠 Utilities

phpSniper includes optional utility helpers:

```php
use Sniper\Utils\DateHelper;
use Sniper\Utils\EventFilter;
use Sniper\Utils\Currency;

// Dates
DateHelper::daysBetween('2024-01-01', '2024-03-15');  // 74
DateHelper::isPast('2023-12-25');                      // true
DateHelper::diffForHumans('2024-01-01', '2024-04-01'); // "3 months"

// Events
$upcoming = EventFilter::upcoming($events, 'date', 'time');
$next     = EventFilter::next($events, 'date');

// Currency
Currency::format(1234567.89, 'USD');        // "$1,234,567.89"
Currency::formatCompact(2500000, 1, '$');   // "$2.5M"
```

---

## ⚙️ Configuration Options

| Key | Type | Default | Description |
|:---|:---|:---|:---|
| `driver` | string | `mysql` | Database driver (`mysql`, `sqlite`, `pgsql`) |
| `host` | string | `127.0.0.1` | Database host |
| `port` | int\|null | `null` | Database port |
| `database` | string | *(required)* | Database name or SQLite file path |
| `username` | string | `root` | Database username |
| `password` | string | `''` | Database password |
| `charset` | string | `utf8mb4` | Connection charset |
| `prefix` | string | `''` | Table name prefix |
| `options` | array | `[]` | Extra PDO connection options |

### Environment Variables

```bash
# .env
DB_DRIVER=mysql
DB_HOST=localhost
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret
```

```php
Sniper::connectFromEnv();
// or with a .env file path
Sniper::connectFromEnv(__DIR__ . '/.env');
```

---

## 🧪 Testing

```bash
composer test          # Run all tests
composer analyse       # PHPStan static analysis (level 8)
composer lint          # Code style check (PSR-12)
composer check         # All of the above
```

---

## 📋 Requirements

- **PHP 8.1+**
- **PDO extension** (bundled with PHP)
- **pdo_mysql** and/or **pdo_sqlite** driver

---

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

---

## 📄 License

phpSniper is open source software released under the [MIT License](LICENSE).

© [phpSniper](https://github.com/phpSniper/php-sniper)

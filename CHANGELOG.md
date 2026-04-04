# Changelog

All notable changes to phpSniper will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-03-31

### ⚠️ Breaking Changes

- Complete rewrite from procedural functions to OOP with namespaces
- Minimum PHP version raised from 5.4 to **8.1**
- Migrated from `mysqli` to **PDO** for multi-database support
- All old function names (`insert()`, `selectAll()`, `delete()`, etc.) replaced by the fluent `QueryBuilder` API
- Removed global `connect()` function — replaced by `Sniper::connect()`

### 🔒 Security

- **All queries now use PDO prepared statements** — eliminates SQL injection vulnerabilities
- Disabled PDO emulated prepares by default (uses native database-level prepared statements)
- Operator whitelist in `where()` prevents operator injection attacks
- Database credentials removed from source — loaded via config array or environment variables

### ✨ Added

- **Fluent Query Builder** — chainable API: `Sniper::table('users')->where('active', true)->get()`
- **WHERE clause variants** — `where()`, `orWhere()`, `whereIn()`, `whereNotIn()`, `whereNull()`, `whereNotNull()`, `whereBetween()`, `whereRaw()`
- **JOINs** — `join()`, `leftJoin()`, `rightJoin()`
- **Aggregates** — `count()`, `sum()`, `avg()`, `min()`, `max()`
- **Pagination** — `paginate()` with JSON-serializable `Paginator`
- **Transactions** — `Sniper::transaction()` with automatic commit/rollback and savepoint nesting
- **Collection** — result wrapper with `map()`, `filter()`, `pluck()`, `groupBy()`, `chunk()`, `sortBy()`, `unique()`, etc.
- **Multi-database support** — MySQL, SQLite, PostgreSQL via driver-specific grammars
- **Table prefixes** — configure prefix once, applied everywhere
- **Batch insert** — `insertBatch()` for efficient multi-row inserts
- **MySQL-specific** — `INSERT IGNORE`, `ON DUPLICATE KEY UPDATE` (upsert)
- **Debugging** — `toSql()` and `toRawSql()` for query inspection
- **Existence checks** — `exists()` and `doesntExist()`
- **Environment config** — load from `.env` files or `DB_*` environment variables
- **Custom exceptions** — `SniperException`, `ConnectionException`, `QueryException` with full SQL context

### ♻️ Changed

- **Architecture** — modular OOP classes with PSR-4 autoloading via Composer
- **Error handling** — exceptions replace `die()` calls
- **Connection management** — lazy-initialized singleton PDO with secure defaults
- **Utility functions** — modernized with `DateTimeImmutable`, moved to `Sniper\Utils` namespace
- **Currency formatting** — locale-aware via `intl` extension with fallback

### 🗑️ Removed

- All legacy procedural functions in `functions/` directory
- `sniper.php` include-chain entry point (replaced by Composer autoloading)
- Hardcoded database credentials in `config.php`

### 📖 Documentation

- Complete README rewrite with API reference and examples
- Contributing Guide (CONTRIBUTING.md)
- Security Policy (SECURITY.md)
- GitHub Issue and PR templates
- Inline PHPDoc for all public methods

### 🧪 Testing

- 143 tests with 230 assertions
- Unit tests for Grammar, Collection, Config, QueryBuilder, and utilities
- Integration tests for Connection, CRUD operations, and Transactions
- PHPStan level 8 static analysis
- PSR-12 code style enforcement via PHP-CS-Fixer
- GitHub Actions CI matrix (PHP 8.1–8.4)

## [1.0.0] - 2023-01-01

### Added

- Initial release with procedural functions
- Basic CRUD operations (`insert`, `selectAll`, `selectRow`, `delete`, `updateRow`)
- Utility functions (currency formatting, date helpers, event filtering)

# Contributing to phpSniper

First off, **thank you** for considering contributing to phpSniper! Every contribution helps make this library better for PHP developers worldwide.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Requesting Features](#requesting-features)
- [Release Process](#release-process)

---

## Code of Conduct

This project adheres to a simple code of conduct: **be respectful, be constructive, and be inclusive.** We are committed to providing a welcoming and harassment-free experience for everyone.

---

## Getting Started

1. **Fork** the repository on GitHub
2. **Clone** your fork locally
3. **Create a branch** for your contribution
4. **Make your changes**
5. **Test** your changes
6. **Submit a Pull Request**

---

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer 2.x
- PDO extension with SQLite driver (for testing)

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/php-sniper.git
cd php-sniper

# Install dependencies
composer install

# Verify everything works
composer check
```

### Available Commands

| Command | Description |
|:---|:---|
| `composer test` | Run the PHPUnit test suite |
| `composer analyse` | Run PHPStan static analysis (level 8) |
| `composer lint` | Check code style (PSR-12) |
| `composer format` | Auto-fix code style issues |
| `composer check` | Run lint + analyse + test |

---

## How to Contribute

### Types of Contributions

- 🐛 **Bug Fixes** — Fix reported issues
- ✨ **Features** — Add new functionality
- 📖 **Documentation** — Improve docs, examples, docblocks
- 🧪 **Tests** — Increase test coverage
- ♻️ **Refactoring** — Improve code quality without changing behavior

### Before You Start

1. **Check existing issues** to see if someone is already working on it
2. **Open an issue first** for any significant changes to discuss the approach
3. **For small fixes** (typos, minor bugs), feel free to submit a PR directly

---

## Coding Standards

### Style

- **PSR-12** coding standard (enforced via PHP-CS-Fixer)
- Run `composer format` to auto-fix style issues
- Every file must include `declare(strict_types=1);`

### Naming

- Classes: `PascalCase` (e.g., `QueryBuilder`)
- Methods: `camelCase` (e.g., `selectAll`)
- Variables: `camelCase` (e.g., `$rowCount`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `SUPPORTED_DRIVERS`)

### Documentation

- Every public method must have a PHPDoc block
- Include `@param` and `@return` type annotations
- Use `@throws` for methods that throw exceptions
- Add `@example` blocks for non-obvious usage patterns

### Testing

- Every new feature must include tests
- Every bug fix must include a regression test
- Tests use **SQLite in-memory** databases (no external DB required)
- Aim for clear, descriptive test method names: `testWhereInFiltersCorrectly()`

### Type Safety

- PHPStan level 8 (maximum strictness)
- Avoid `mixed` types where possible
- Use union types and generics (`@template`) appropriately

---

## Pull Request Process

### Branch Naming

```
feature/add-subquery-support
fix/where-null-compilation-error
docs/improve-pagination-examples
refactor/extract-grammar-interface
```

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) format:

```
feat: add whereExists() method to QueryBuilder
fix: handle null values in whereBetween()
docs: add migration guide from v1 to v2
test: add integration tests for transactions
refactor: extract common WHERE compilation logic
```

### PR Checklist

Before submitting your PR, ensure:

- [ ] Code follows PSR-12 (`composer lint` passes)
- [ ] PHPStan level 8 passes (`composer analyse`)
- [ ] All tests pass (`composer test`)
- [ ] New features include tests
- [ ] PHPDoc blocks are complete for new public methods
- [ ] CHANGELOG.md is updated (if applicable)

### Review Process

1. A maintainer will review your PR within 48 hours
2. Address any requested changes
3. Once approved, a maintainer will merge your PR
4. Your contribution will be included in the next release 🎉

---

## Reporting Bugs

Use the [Bug Report](https://github.com/phpSniper/php-sniper/issues/new?template=bug_report.md) template. Include:

1. **PHP version** and **database driver**
2. **Steps to reproduce** the issue
3. **Expected behavior** vs **actual behavior**
4. **Minimal code example** that demonstrates the bug

---

## Requesting Features

Use the [Feature Request](https://github.com/phpSniper/php-sniper/issues/new?template=feature_request.md) template. Include:

1. **Problem description** — What are you trying to solve?
2. **Proposed solution** — How would you like it to work?
3. **Code examples** — Show the ideal API usage
4. **Alternatives considered** — What else did you evaluate?

---

## Release Process

phpSniper follows [Semantic Versioning](https://semver.org/):

- **MAJOR** (x.0.0) — Breaking API changes
- **MINOR** (0.x.0) — New features, backward compatible
- **PATCH** (0.0.x) — Bug fixes, backward compatible

Releases are tagged on the `main` branch and published to [Packagist](https://packagist.org/packages/phpsniper/sniper) automatically.

---

Thank you for contributing to phpSniper! 🎯

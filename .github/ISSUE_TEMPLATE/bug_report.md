---
name: Bug Report
about: Report a bug to help us improve phpSniper
title: "[Bug] "
labels: bug
assignees: ''
---

## Description

A clear and concise description of the bug.

## Steps to Reproduce

1. Configure connection with `Sniper::connect([...])`
2. Execute query `Sniper::table('...')->...`
3. See error

## Expected Behavior

What you expected to happen.

## Actual Behavior

What actually happened. Include the full error message if applicable.

## Code Example

```php
// Minimal code to reproduce the issue
Sniper::connect([...]);
$result = Sniper::table('users')->where('id', 1)->first();
```

## Environment

- **PHP Version:** [e.g., 8.2]
- **phpSniper Version:** [e.g., 2.0.0]
- **Database Driver:** [e.g., MySQL 8.0, SQLite 3.x]
- **Operating System:** [e.g., Ubuntu 22.04]

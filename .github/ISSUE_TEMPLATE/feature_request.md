---
name: Feature Request
about: Suggest a new feature for phpSniper
title: "[Feature] "
labels: enhancement
assignees: ''
---

## Problem

Describe the problem or limitation you're experiencing.

## Proposed Solution

How would you like this to work? Include example code if possible.

```php
// Example of the ideal API
$result = Sniper::table('users')
    ->whereExists(function ($query) {
        $query->table('orders')->whereColumn('orders.user_id', 'users.id');
    })
    ->get();
```

## Alternatives Considered

What alternative solutions or features have you considered?

## Additional Context

Any other context, screenshots, or references.

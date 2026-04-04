# Security Policy

## Supported Versions

| Version | Supported |
|:--------|:----------|
| 2.x     | ✅ Yes     |
| 1.x     | ❌ No      |

## Reporting a Vulnerability

If you discover a security vulnerability within phpSniper, please report it responsibly:

1. **Do NOT open a public GitHub issue.**
2. Email your findings to the maintainers directly.
3. Include a detailed description of the vulnerability, steps to reproduce, and (if possible) a suggested fix.

We will acknowledge your report within **48 hours** and aim to release a patch within **7 days** for critical vulnerabilities.

## Security Best Practices When Using phpSniper

1. **Always use the query builder** — it parameterizes all values automatically
2. **Avoid `whereRaw()`** unless absolutely necessary, and always pass bindings
3. **Never expose `QueryException` messages** to end users in production (they contain SQL)
4. **Use environment variables** for database credentials — never hardcode them
5. **Apply the principle of least privilege** — your database user should only have necessary permissions

## Security Design

phpSniper v2.0 was designed with security as a core principle:

- ✅ **100% prepared statements** — all values are bound via PDO
- ✅ **Emulated prepares disabled** — uses native database-level prepared statements
- ✅ **Operator whitelist** — prevents operator injection in `where()` clauses
- ✅ **Exception-based error handling** — no data leaks via `die()` or warnings
- ✅ **No credentials in source code** — configuration via arrays or environment variables

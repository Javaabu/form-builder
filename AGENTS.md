# form-builder: notes for AI coding agents

`javaabu/form-builder` is a Laravel package for dynamic forms stored in the database, compiled to javaabu/stencil schemas.

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…`, early returns, no `else`. Pint enforces formatting.
- Work on `main`. Never tag or release.

## Commands

```bash
composer install
composer lint && composer test        # Pint and PHPUnit
```

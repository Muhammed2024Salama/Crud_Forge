# CrudForge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/muhammedsalama/crudforge.svg?style=flat-square)](https://packagist.org/packages/muhammedsalama/crudforge)
[![Tests](https://img.shields.io/github/actions/workflow/status/Muhammed2024Salama/Crud_Forge/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/Muhammed2024Salama/Crud_Forge/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/Muhammed2024Salama/Crud_Forge/ci.yml?branch=main&label=static+analysis&style=flat-square)](https://github.com/Muhammed2024Salama/Crud_Forge/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/muhammedsalama/crudforge.svg?style=flat-square)](https://packagist.org/packages/muhammedsalama/crudforge)
[![License](https://img.shields.io/packagist/l/muhammedsalama/crudforge.svg?style=flat-square)](https://packagist.org/packages/muhammedsalama/crudforge)

Generate production-ready Laravel CRUD modules from a single Artisan command.

Each generated module follows clean architecture — model, API controller, service, repository, interface, form requests, API resource, migration, factory, feature tests, and translations. The generator wires everything automatically: no manual provider registration, no manual route includes, no manual binding setup.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `11`, `12`, `13` |

> **Note:** PHP 8.2 is not compatible with Laravel 13 (which requires PHP ≥ 8.3). The CI matrix enforces this constraint explicitly.

---

## Installation

```bash
composer require muhammedsalama/crudforge:^1.2.0
```

The `^1.2.0` constraint allows any backward-compatible update (`1.2.x`, `1.3.0`, etc.) while blocking major versions that may contain breaking changes. To upgrade to a future major version, review the migration guide in [CHANGELOG.md](CHANGELOG.md) first.

Laravel auto-discovery registers both service providers automatically. No manual registration is required.

Optionally run the install command to publish the config and confirm the setup:

```bash
php artisan crudforge:install
```

---

## Updating the Package

```bash
composer update muhammedsalama/crudforge
```

Patch and minor releases are fully backward-compatible. No changes to your configuration, generated files, or application code are required.

For major version upgrades, see the [Upgrade Guide](#upgrade-guide) below and the relevant entry in [CHANGELOG.md](CHANGELOG.md).

---

## Usage

### Generate a module

```bash
php artisan crudforge:generate Order --fields="customer_name:string,total:decimal,status:boolean,note:text"
```

That single command:

1. Generates all files for the `Order` module
2. Writes the repository binding to `bootstrap/crudforge-bindings.php`
3. Registers the route file in `routes/crudforge.php`
4. Reports the live API endpoint

### Generated output

```
app/
├── Http/
│   ├── Controllers/Api/OrderController.php
│   ├── Requests/StoreOrderRequest.php
│   ├── Requests/UpdateOrderRequest.php
│   └── Resources/OrderResource.php
├── Interfaces/
│   └── OrderRepositoryInterface.php
├── Models/
│   └── Order.php
├── Repositories/
│   └── OrderRepository.php
└── Services/
    └── OrderService.php

bootstrap/
└── crudforge-bindings.php  ← updated automatically; commit this file

database/
├── factories/OrderFactory.php
└── migrations/xxxx_xx_xx_create_orders_table.php

lang/
├── en/orders.php
└── ar/orders.php

routes/
├── crudforge-orders.php    ← route definitions for this module
└── crudforge.php           ← route registry; updated automatically, commit this file

tests/
└── Feature/OrderApiTest.php
```

### What is automatic

| Step | Handled by |
|---|---|
| Service provider registration | Laravel auto-discovery |
| Hydrating repository bindings at runtime | `CrudForgeRuntimeServiceProvider` |
| Loading the `routes/crudforge.php` manifest | `CrudForgeRuntimeServiceProvider` |
| Writing binding to `bootstrap/crudforge-bindings.php` | `crudforge:generate` |
| Registering route in `routes/crudforge.php` | `crudforge:generate` |

`bootstrap/providers.php`, `bootstrap/app.php`, and `routes/api.php` are **never modified**.

### API endpoints

After generating the `Order` module, the following REST endpoints are available:

```
GET    /api/orders            Paginated index
POST   /api/orders            Create
GET    /api/orders/{order}    Show
PUT    /api/orders/{order}    Update
DELETE /api/orders/{order}    Destroy
```

The `/api` prefix is controlled by `crudforge.defaults.route_prefix` in the published config. See [Configuration](#configuration).

Supported query parameters on `GET /api/orders`:

| Parameter | Description |
|---|---|
| `search` | Full-text search across string/text fields |
| `sort_by` | Column name (validated against an allowlist) |
| `sort_direction` | `asc` or `desc` |
| `per_page` | 1–100, default 15 |

---

## Field Types

| Alias | Normalised type | Migration column | Eloquent cast |
|---|---|---|---|
| `string`, `str`, `varchar` | `string` | `string()` | — |
| `text` | `text` | `text()->nullable()` | — |
| `integer`, `int` | `integer` | `integer()` | — |
| `bigInteger`, `bigint` | `bigInteger` | `bigInteger()` | — |
| `unsignedInteger`, `uint` | `unsignedInteger` | `unsignedInteger()` | — |
| `unsignedBigInteger`, `ubigint` | `unsignedBigInteger` | `unsignedBigInteger()` | — |
| `boolean`, `bool` | `boolean` | `boolean()->default(false)` | `boolean` |
| `decimal`, `float`, `double` | `decimal` | `decimal(10, 2)` | `decimal:2` |
| `date` | `date` | `date()->nullable()` | `date` |
| `datetime` | `datetime` | `dateTime()->nullable()` | `datetime` |
| `timestamp` | `timestamp` | `timestamp()->nullable()` | `datetime` |
| `json`, `jsonb` | `json` | `json()->nullable()` | `array` |
| `foreignId`, `foreign_id` | `foreignId` | `foreignId()->constrained()->cascadeOnDelete()` | — |

Validation rules are derived automatically from the field type:

- `StoreRequest` — required fields use `'required'`; nullable types (`text`, `date`, `datetime`, `json`) use `'nullable'`
- `UpdateRequest` — all fields use `'sometimes'` for safe partial updates

---

## Command Reference

```bash
# One-time setup — publishes config and prints a setup summary
php artisan crudforge:install

# Generate a full CRUD module
php artisan crudforge:generate {Name} --fields="field:type,..."

# Preview what would be generated without writing any files
php artisan crudforge:generate {Name} --fields="..." --dry-run

# Overwrite existing files without confirmation
php artisan crudforge:generate {Name} --fields="..." --force

# Skip automatic registry updates (binding + route manifest)
php artisan crudforge:generate {Name} --fields="..." --no-auto-register

# Re-wire a specific module's binding (e.g. after pulling from Git)
php artisan crudforge:install-bindings Order

# Re-wire all modules at once
php artisan crudforge:install-bindings --all
```

---

## Configuration

Publish the config to customise output paths, namespaces, and runtime defaults:

```bash
php artisan vendor:publish --tag=crudforge-config
```

```php
// config/crudforge.php

'paths' => [
    'models'      => app_path('Models'),
    'controllers' => app_path('Http/Controllers/Api'),
    // ...
],

'namespaces' => [
    'models'      => 'App\\Models',
    'controllers' => 'App\\Http\\Controllers\\Api',
    // ...
],

'defaults' => [
    'pagination_per_page' => 15,

    'route_prefix' => 'api',
],

'auto_register' => true,
```

> **Important:** If you customise the API route prefix in `bootstrap/app.php` (e.g. `withRouting(apiPrefix: 'v1')`), you must set `crudforge.defaults.route_prefix` to the same value (`'v1'`). Otherwise generated routes and generated tests will use the wrong prefix.

Publish stubs to customise the generated code templates:

```bash
php artisan vendor:publish --tag=crudforge-stubs
```

Stubs are published to `stubs/vendor/crudforge/`. CrudForge checks for a published stub before falling back to the built-in one, so you only need to publish the stubs you intend to modify.

---

## Architecture

Every generated module follows a strict layered architecture:

```
HTTP Request
     │
     ▼
Controller        ← HTTP layer only; no business logic
     │
     ▼
Service           ← Business logic; depends on the interface, not the concrete class
     │
     ▼
RepositoryInterface  ← Contract; makes the concrete class swappable
     │
     ▼
Repository        ← Eloquent queries; implements the interface
     │
     ▼
Model
```

The generated code has **zero runtime dependency on CrudForge**. Once generation is complete, the package can be moved to `require-dev` or removed entirely without affecting the running application.

---

## Database Compatibility

The search logic in generated repositories is driver-aware and inlined — no runtime package dependency:

| Driver | Operator |
|---|---|
| MySQL / SQLite | `LIKE` |
| PostgreSQL | `ILIKE` (case-insensitive) |

---

## Extending CrudForge

Tag any `GeneratorContract` implementation with `crudforge.generators` in your own service provider to add it to the generation pipeline without modifying the package:

```php
use MuhammedSalama\CrudForge\Contracts\GeneratorContract;

// In your ServiceProvider::register():
$this->app->bind(PolicyGenerator::class);
$this->app->tag([PolicyGenerator::class], 'crudforge.generators');
```

Your generator runs after the built-in ones on every `crudforge:generate` call.

---

## Safety

- Files are never overwritten without confirmation unless `--force` is passed
- Declining a single overwrite skips that file and continues the rest of the run
- All output paths are validated against `base_path()` to prevent path traversal
- Model names must match `[A-Za-z][A-Za-z0-9]*`
- Field names must match `[a-z][a-z0-9_]*`

---

## Upgrade Guide

### Upgrading from 1.1.x to 1.2.x

1.2.x is a **compatibility-only release**. No changes to the public API, generated output, or configuration.

```bash
composer update muhammedsalama/crudforge
```

No further action is required.

---

### Upgrading from 1.0.x to 1.1.x

1.1.x introduced the runtime registry system that replaces direct `bootstrap/providers.php` editing.

**Step 1 — Update the package**

```bash
composer update muhammedsalama/crudforge
```

**Step 2 — Remove any manual CrudForge entries from `bootstrap/providers.php`**

If a previous version added a provider entry for CrudForge to `bootstrap/providers.php`, remove it. Both providers are now registered via Composer auto-discovery.

**Step 3 — Remove manual route includes from `routes/api.php`**

If you previously added `require __DIR__.'/crudforge-*.php'` lines to `routes/api.php`, remove them. Routes are now loaded from a single manifest (`routes/crudforge.php`) by `CrudForgeRuntimeServiceProvider`.

**Step 4 — Regenerate the binding registry**

```bash
php artisan crudforge:install-bindings --all
```

This creates `bootstrap/crudforge-bindings.php`. Commit it to version control.

**Step 5 — Clear caches**

```bash
php artisan config:clear
php artisan route:clear
```

---

## Troubleshooting

### Routes return 404

Generated routes are loaded automatically by the service provider. If you have cached routes, clear the cache:

```bash
php artisan route:clear
```

If you previously added a manual `require __DIR__.'/crudforge-*.php'` in `routes/api.php`, **remove it** — routes are now auto-loaded via `routes/crudforge.php` and the manual require causes duplicate registration.

If you have changed the API prefix in `bootstrap/app.php`, ensure `crudforge.defaults.route_prefix` in your published config matches it.

### "Target [Interface] is not instantiable"

The binding registry is missing or incomplete. Re-run the binding setup:

```bash
php artisan crudforge:install-bindings --all
```

Then verify that `bootstrap/crudforge-bindings.php` exists and contains the expected interface-to-repository mapping. If the file was accidentally deleted or excluded from version control, re-running the above command regenerates it. Ensure the file is committed — it is a CrudForge-owned file, not a Laravel core file.

### Config not found / stale

```bash
php artisan vendor:publish --tag=crudforge-config
php artisan config:clear
```

### Regenerating an existing module

Use `--force` to overwrite all files, or omit it to be asked per file:

```bash
php artisan crudforge:generate Order --fields="..." --force
```

Regenerating does not create a duplicate migration file — CrudForge detects the existing migration and overwrites it in place.

---

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

---

## Versioning Strategy

This package follows [Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`):

| Increment | When | Example |
|---|---|---|
| `PATCH` | Bug fixes and internal improvements; no API changes | `1.2.0` → `1.2.1` |
| `MINOR` | New features added in a backward-compatible manner | `1.2.0` → `1.3.0` |
| `MAJOR` | Breaking changes to the public API or generated output | `1.2.0` → `2.0.0` |

Patch and minor updates are safe to apply via `composer update` without reviewing generated files or configuration. Major version bumps will always include a migration guide in [CHANGELOG.md](CHANGELOG.md).

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full release history.

---

## Contributing

Contributions are welcome. Please open an issue before submitting a pull request for significant changes.

## License

The MIT License. See [LICENSE](LICENSE).

---

## Support

For support, bug reports, or feature requests:

- Email: devmuhammedsalama@gmail.com
- Issues: [GitHub Issues](https://github.com/Muhammed2024Salama/Crud_Forge/issues)

---

## Acknowledgments

- Built for Laravel developers who want production-ready CRUD scaffolding
- Inspired by Laravel clean architecture and modern package development practices
- Special thanks to all contributors and testers

---

## Related Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Packagist Package](https://packagist.org/packages/muhammedsalama/crudforge)
- [PHPStan](https://phpstan.org/)
- [Orchestra Testbench](https://github.com/orchestral/testbench)

---

Made with ❤️ by Muhammed Salama
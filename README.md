# CrudForge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/muhammedsalama/crudforge.svg?style=flat-square)](https://packagist.org/packages/muhammedsalama/crudforge)
[![Tests](https://img.shields.io/github/actions/workflow/status/Muhammed2024Salama/Crud_Forge/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/Muhammed2024Salama/Crud_Forge/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/Muhammed2024Salama/Crud_Forge/ci.yml?branch=main&label=static+analysis&style=flat-square)](https://github.com/Muhammed2024Salama/Crud_Forge/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/muhammedsalama/crudforge.svg?style=flat-square)](https://packagist.org/packages/muhammedsalama/crudforge)
[![License](https://img.shields.io/packagist/l/muhammedsalama/crudforge.svg?style=flat-square)](https://packagist.org/packages/muhammedsalama/crudforge)

Generate production-ready Laravel CRUD modules from a single Artisan command.

Each generated module follows clean architecture — model, API controller, service, repository, interface, form requests, API resource, migration, factory, feature tests, and translations. The generator wires everything automatically: no manual provider registration, no manual route includes, no manual binding setup.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^11.0 \| ^12.0 \| ^13.0` |

## Installation

```bash
composer require muhammedsalama/crudforge
```

Laravel auto-discovery registers the service provider. Nothing else is required.

Optionally run the install command to publish the config and see a setup summary:

```bash
php artisan crudforge:install
```

## Usage

### Generate a module

```bash
php artisan crudforge:generate Order --fields="customer_name:string,total:decimal,status:boolean,note:text"
```

That single command:

1. Generates all files for the `Order` module
2. Creates or updates `App\Providers\CrudForgeGeneratedServiceProvider` with the repository binding
3. Registers that provider in `bootstrap/providers.php` (if not already there)
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
├── Providers/
│   └── CrudForgeGeneratedServiceProvider.php
├── Repositories/
│   └── OrderRepository.php
└── Services/
    └── OrderService.php

database/
├── factories/OrderFactory.php
└── migrations/xxxx_xx_xx_create_orders_table.php

lang/
├── en/orders.php
└── ar/orders.php

routes/
└── crudforge-orders.php   ← loaded automatically, no require needed

tests/
└── Feature/OrderApiTest.php
```

### What is automatic

| Step | Handled by |
|---|---|
| Service provider registration | Laravel auto-discovery |
| Loading `routes/crudforge-*.php` | `CrudForgeServiceProvider` |
| Creating `CrudForgeGeneratedServiceProvider` | `crudforge:generate` |
| Appending the repository binding | `crudforge:generate` |
| Registering the provider in `bootstrap/providers.php` | `crudforge:generate` |

No edits to `bootstrap/app.php`, `bootstrap/providers.php`, or `routes/api.php` are required.

### API endpoints

After generating the `Order` module, the following REST endpoints are live:

```
GET    /api/orders            Paginated index
POST   /api/orders            Create
GET    /api/orders/{order}    Show
PUT    /api/orders/{order}    Update
DELETE /api/orders/{order}    Destroy
```

Supported query parameters on `GET /api/orders`:

| Parameter | Description |
|---|---|
| `search` | Full-text search across string/text fields |
| `sort_by` | Column name (validated against an allowlist) |
| `sort_direction` | `asc` or `desc` |
| `per_page` | 1–100, default 15 |

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

Validation rules are derived from the field type automatically:

- `StoreRequest` — required fields use `'required'`; nullable types use `'nullable'`
- `UpdateRequest` — all fields use `'sometimes'` for safe partial updates

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

# Re-wire a specific module's binding (e.g. after pulling from Git)
php artisan crudforge:install-bindings Order

# Re-wire all modules at once
php artisan crudforge:install-bindings --all
```

## Configuration

Publish the config to customise output paths and namespaces:

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
    'route_prefix'        => 'api',  // URL prefix for auto-loaded routes
],
```

Publish stubs to customise the generated code templates:

```bash
php artisan vendor:publish --tag=crudforge-stubs
```

Stubs are published to `stubs/vendor/crudforge/`. CrudForge checks for a published stub before falling back to the built-in one, so you only need to publish the stubs you want to change.

## Architecture

Every generated module follows a strict layered architecture:

```
HTTP Request
     │
     ▼
Controller        ← HTTP only; no business logic
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

## Database Compatibility

The search logic in generated repositories is driver-aware and inlined — no runtime package dependency:

| Driver | Operator |
|---|---|
| MySQL / SQLite | `LIKE` |
| PostgreSQL | `ILIKE` (case-insensitive) |

## Extending CrudForge

Tag any `GeneratorContract` implementation with `crudforge.generators` in your own service provider to add it to the generation pipeline without modifying the package:

```php
use MuhammedSalama\CrudForge\Contracts\GeneratorContract;

// In your ServiceProvider::register():
$this->app->bind(PolicyGenerator::class);
$this->app->tag([PolicyGenerator::class], 'crudforge.generators');
```

Your generator runs after the built-in ones on every `crudforge:generate` call.

## Safety

- Files are never overwritten without confirmation unless `--force` is passed
- Declining a single overwrite skips that file and continues the rest of the run
- All output paths are validated against `base_path()` to prevent path traversal
- Model names must match `[A-Za-z][A-Za-z0-9]*`
- Field names must match `[a-z][a-z0-9_]*`

## Troubleshooting

### Routes return 404

Generated routes are loaded automatically by the service provider. If you have cached routes, clear the cache:

```bash
php artisan route:clear
```

If you previously added a manual `require __DIR__.'/crudforge-*.php'` in `routes/api.php`, **remove it** — routes are now auto-loaded and the manual require causes duplicate registration.

### "Target [Interface] is not instantiable"

The generated service provider is not registered. Re-run the binding setup:

```bash
php artisan crudforge:install-bindings --all
```

Then verify that `App\Providers\CrudForgeGeneratedServiceProvider::class` is present in `bootstrap/providers.php`.

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

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

Contributions are welcome. Please open an issue before submitting a pull request for significant changes.

## License

The MIT License. See [LICENSE](LICENSE).

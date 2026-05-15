# CrudForge

CrudForge is a Laravel package that generates production-ready CRUD modules inside any existing Laravel application using a single Artisan command.

It focuses on clean architecture, deterministic stub-based generation, zero runtime coupling, and MySQL/PostgreSQL-compatible generated code.

---

## Installation

```bash
composer require muhammedsalama/crudforge
```

Laravel package auto-discovery registers the service provider automatically.

Publish the config (optional):

```bash
php artisan vendor:publish --tag=crudforge-config
```

Publish stubs to customise them (optional):

```bash
php artisan vendor:publish --tag=crudforge-stubs
```

---

## Generate a CRUD Module

```bash
php artisan crudforge:generate Product --fields="name:string,price:decimal,status:boolean"
```

### Generated files

```text
app/Models/Product.php
app/Http/Controllers/Api/ProductController.php
app/Services/ProductService.php
app/Repositories/ProductRepository.php
app/Interfaces/ProductRepositoryInterface.php
app/Http/Requests/StoreProductRequest.php
app/Http/Requests/UpdateProductRequest.php
app/Http/Resources/ProductResource.php
database/migrations/*_create_products_table.php
database/factories/ProductFactory.php
tests/Feature/ProductApiTest.php
lang/en/products.php
lang/ar/products.php
routes/crudforge-products.php
```

### Options

| Option | Description |
|---|---|
| `--fields=` | Comma-separated `name:type` pairs (required) |
| `--force` | Overwrite all existing files without confirmation |

Without `--force`, CrudForge asks per file and **skips** declined files without aborting the entire run.

---

## Install Repository Bindings

Generated services depend on repository interfaces. After generating a module, wire the binding:

```bash
php artisan crudforge:install-bindings Product
```

This creates or updates `app/Providers/CrudForgeGeneratedServiceProvider.php`:

```php
$this->app->bind(
    \App\Interfaces\ProductRepositoryInterface::class,
    \App\Repositories\ProductRepository::class
);
```

Detect and install all generated bindings at once:

```bash
php artisan crudforge:install-bindings --all
```

Register the provider in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\CrudForgeGeneratedServiceProvider::class,
];
```

---

## Route Snippet

CrudForge writes a standalone route file:

```text
routes/crudforge-products.php
```

Require it from `routes/api.php`:

```php
require __DIR__.'/crudforge-products.php';
```

---

## Supported Field Types

| Input | Normalised type | Migration column |
|---|---|---|
| `string`, `str`, `varchar` | `string` | `string()` |
| `text` | `text` | `text()->nullable()` |
| `integer`, `int` | `integer` | `integer()` |
| `bigInteger`, `bigint` | `bigInteger` | `bigInteger()` |
| `unsignedInteger`, `uint` | `unsignedInteger` | `unsignedInteger()` |
| `unsignedBigInteger`, `ubigint` | `unsignedBigInteger` | `unsignedBigInteger()` |
| `boolean`, `bool` | `boolean` | `boolean()->default(false)` |
| `decimal`, `float`, `double` | `decimal` | `decimal(10,2)` |
| `date` | `date` | `date()->nullable()` |
| `datetime` | `datetime` | `dateTime()->nullable()` |
| `timestamp` | `timestamp` | `timestamp()->nullable()` |
| `json`, `jsonb` | `json` | `json()->nullable()` |
| `foreignId`, `foreign_id` | `foreignId` | `foreignId()->constrained()->cascadeOnDelete()` |

**Validation rules** automatically use `'required'` in `StoreRequest` and `'sometimes'` in `UpdateRequest`.

**Eloquent `$casts`** are generated automatically for `boolean`, `date`, `datetime`, `timestamp`, `decimal`, and `json` fields.

---

## Generated Code Independence

Generated code has **zero runtime dependency** on the CrudForge package:

- Repositories use an inlined driver-aware search (MySQL `LIKE` / PostgreSQL `ILIKE`) rather than importing the package helper.
- The package may be listed in `require-dev` after generation if your application no longer needs the generator at runtime.

---

## Customising Output Paths & Namespaces

Publish the config and change `paths` or `namespaces` keys:

```php
// config/crudforge.php
'paths' => [
    'models'      => app_path('Domain/Models'),
    'controllers' => app_path('Http/Controllers/Api'),
    // ...
],

'namespaces' => [
    'models'      => 'App\\Domain\\Models',
    'controllers' => 'App\\Http\\Controllers\\Api',
    // ...
],
```

All generators read these values at generation time.

---

## Adding Custom Generators

Tag any `GeneratorContract` implementation with `'crudforge.generators'` in your own service provider to include it in the generation pipeline without modifying the package:

```php
use MuhammedSalama\CrudForge\Contracts\GeneratorContract;

$this->app->bind(PolicyGenerator::class, PolicyGenerator::class);
$this->app->tag([PolicyGenerator::class], 'crudforge.generators');
```

Note: custom generators added this way currently need the `GeneratorOrchestrator` to be re-bound to include them. An automatic registration path is planned.

---

## Database Compatibility

| Driver | Search operator |
|---|---|
| PostgreSQL | `ILIKE` (case-insensitive) |
| MySQL / SQLite | `LIKE` |

The `per_page` parameter is automatically clamped to `[1, 100]` in generated repositories.

---

## Safety

- Files are never overwritten without confirmation unless `--force` is passed.
- Declining to overwrite a single file **skips** it and continues generating remaining files.
- Regenerating the same module reuses the existing migration file instead of creating a timestamp collision.
- Output paths are validated against `base_path()` to prevent path traversal.
- Model names must match `[A-Za-z][A-Za-z0-9]*` (PascalCase, no underscores at root level).
- Field names must match `[a-z][a-z0-9_]*`.

---

## Testing the Package

```bash
composer install
vendor/bin/phpunit
```

---

## Current Limitations

- Relationship-aware factories are planned for a later phase.
- Automatic route appending to `routes/api.php` is planned for a later phase.
- The `--all` detection in `crudforge:install-bindings` scans `app/Interfaces` — configurable path support is planned.

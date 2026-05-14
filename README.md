# CrudForge

CrudForge is a Laravel package that generates production-ready CRUD modules inside any existing Laravel application using Artisan commands.

It focuses on clean Laravel architecture, deterministic stub-based generation, and MySQL/PostgreSQL-compatible generated code.

## Package Name

```bash
composer require muhammedsalama/crudforge
```

Composer package names must be lowercase:

```text
muhammedsalama/crudforge
```

Internal PHP namespace:

```php
MuhammedSalama\CrudForge
```

Generated Laravel application files still use normal app namespaces:

```php
App\Models\Product
App\Http\Controllers\Api\ProductController
App\Services\ProductService
App\Repositories\ProductRepository
App\Interfaces\ProductRepositoryInterface
```

---

## Local Path Installation

Inside your Laravel application `composer.json`:

```json
"repositories": [
  {
    "type": "path",
    "url": "../crudforge"
  }
]
```

Then run:

```bash
composer require muhammedsalama/crudforge:@dev
composer dump-autoload
php artisan vendor:publish --tag=crudforge-config
```

Laravel package auto-discovery registers the package provider automatically:

```php
MuhammedSalama\CrudForge\CrudForgeServiceProvider::class
```

---

## Generate CRUD

```bash
php artisan crudforge:generate Product --fields="name:string,price:decimal,status:boolean"
```

Generated files:

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

---

## Install Repository Bindings

Generated controllers depend on services, and generated services depend on repository interfaces. After generating a module, install the repository binding provider:

```bash
php artisan crudforge:install-bindings Product
```

This creates or updates:

```text
app/Providers/CrudForgeGeneratedServiceProvider.php
```

Example generated binding:

```php
$this->app->bind(
    \App\Interfaces\ProductRepositoryInterface::class,
    \App\Repositories\ProductRepository::class
);
```

You can also scan all generated repository interfaces and repositories:

```bash
php artisan crudforge:install-bindings --all
```

If the provider already exists, CrudForge safely appends only missing bindings and avoids duplicates.

To skip confirmation in automation:

```bash
php artisan crudforge:install-bindings Product --force
php artisan crudforge:install-bindings --all --force
```

### Register Generated Provider

Laravel 11/12/13 uses `bootstrap/providers.php`.

Add this line if it is not already registered:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\CrudForgeGeneratedServiceProvider::class,
];
```

---

## Route Snippet

CrudForge creates a route snippet file:

```text
routes/crudforge-products.php
```

Require it from your application `routes/api.php`:

```php
require __DIR__.'/crudforge-products.php';
```

---

## Supported Field Types

```text
string
text
integer
bigInteger
boolean
decimal
float
date
datetime
timestamp
json
foreignId
```

Notes:

- `json` is used instead of `jsonb` for MySQL/PostgreSQL compatibility.
- Database enum columns are not generated.
- Use string columns plus FormRequest validation instead.
- Search uses a database-driver-aware helper.

---

## Database Compatibility

CrudForge includes:

```php
MuhammedSalama\CrudForge\Support\Database\DatabaseDriverHelper
```

Search behavior:

| Driver | Operator |
|---|---|
| PostgreSQL | ILIKE |
| MySQL | LIKE |

Generated repositories use safe searchable fields, safe sortable fields, and no raw user input in `orderBy`.

---

## Safety

CrudForge checks before overwriting existing files and asks for confirmation before replacement.

The binding installer also asks before updating an existing provider unless `--force` is passed.

---

## Testing The Package

From the package directory:

```bash
composer install
composer dump-autoload
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

---

## Current Limitations

- Generated repositories currently depend on `MuhammedSalama\CrudForge\Support\Database\DatabaseDriverHelper`, so the package should remain installed in projects using generated modules.
- Relationship-aware factories are planned for a later phase.
- Automatic route appending to `routes/api.php` is planned for a later phase.

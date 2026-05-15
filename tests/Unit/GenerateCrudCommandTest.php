<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use MuhammedSalama\CrudForge\Console\Commands\GenerateCrudCommand;
use MuhammedSalama\CrudForge\Tests\TestCase;

final class GenerateCrudCommandTest extends TestCase
{
    /** Paths written during generation so tearDown can clean up. */
    private array $generatedPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPaths = [
            app_path('Models/Product.php'),
            app_path('Http/Controllers/Api/ProductController.php'),
            app_path('Interfaces/ProductRepositoryInterface.php'),
            app_path('Repositories/ProductRepository.php'),
            app_path('Services/ProductService.php'),
            app_path('Http/Requests/StoreProductRequest.php'),
            app_path('Http/Requests/UpdateProductRequest.php'),
            app_path('Http/Resources/ProductResource.php'),
            database_path('factories/ProductFactory.php'),
            base_path('routes/crudforge-products.php'),
            base_path('tests/Feature/ProductApiTest.php'),
            lang_path('en/products.php'),
            lang_path('ar/products.php'),
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->generatedPaths as $path) {
            File::delete($path);
        }

        foreach (glob(database_path('migrations/*_create_products_table.php')) ?: [] as $f) {
            File::delete($f);
        }

        File::delete(base_path('bootstrap/crudforge-bindings.php'));
        File::delete(base_path('routes/crudforge.php'));

        parent::tearDown();
    }

    public function test_command_generates_all_expected_files(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string,price:decimal,status:boolean --force')
            ->assertSuccessful();

        foreach ($this->generatedPaths as $path) {
            $this->assertFileExists($path, "Expected file was not generated: {$path}");
        }

        $migrationFiles = glob(database_path('migrations/*_create_products_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file was not generated');
    }

    public function test_model_has_correct_namespace_and_fillable(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string,price:decimal --force')
            ->assertSuccessful();

        $content = File::get(app_path('Models/Product.php'));

        $this->assertStringContainsString('namespace App\\Models;', $content);
        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'price'", $content);
    }

    public function test_model_includes_casts_for_castable_types(): void
    {
        $this->artisan('crudforge:generate Product --fields=active:boolean,meta:json,amount:decimal --force')
            ->assertSuccessful();

        $content = File::get(app_path('Models/Product.php'));

        $this->assertStringContainsString('$casts', $content);
        $this->assertStringContainsString("'boolean'", $content);
        $this->assertStringContainsString("'array'", $content);
        $this->assertStringContainsString("'decimal:2'", $content);
    }

    public function test_model_omits_casts_when_no_castable_fields(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string,count:integer --force')
            ->assertSuccessful();

        $content = File::get(app_path('Models/Product.php'));

        $this->assertStringNotContainsString('$casts', $content);
    }

    public function test_repository_has_no_crudforge_package_import(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Repositories/ProductRepository.php'));

        $this->assertStringNotContainsString('MuhammedSalama\\CrudForge', $content);
        $this->assertStringNotContainsString('DatabaseDriverHelper', $content);
    }

    public function test_repository_inlines_driver_detection(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Repositories/ProductRepository.php'));

        $this->assertStringContainsString('getDriverName', $content);
        $this->assertStringContainsString('ILIKE', $content);
        $this->assertStringContainsString('LIKE', $content);
    }

    public function test_repository_caps_per_page_at_100(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Repositories/ProductRepository.php'));

        $this->assertStringContainsString('min(', $content);
    }

    public function test_store_request_uses_required(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Http/Requests/StoreProductRequest.php'));

        $this->assertStringContainsString("'required'", $content);
    }

    public function test_update_request_uses_sometimes_not_required(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Http/Requests/UpdateProductRequest.php'));

        $this->assertStringContainsString("'sometimes'", $content);
        $this->assertStringNotContainsString("'required'", $content);
    }

    public function test_service_has_correct_namespace(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Services/ProductService.php'));

        $this->assertStringContainsString('namespace App\\Services;', $content);
        $this->assertStringContainsString('ProductRepositoryInterface', $content);
    }

    public function test_controller_references_service_and_requests(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(app_path('Http/Controllers/Api/ProductController.php'));

        $this->assertStringContainsString('StoreProductRequest', $content);
        $this->assertStringContainsString('UpdateProductRequest', $content);
        $this->assertStringContainsString('ProductService', $content);
        $this->assertStringContainsString('namespace App\\Http\\Controllers\\Api;', $content);
    }

    public function test_route_snippet_is_valid_php_with_apiresource(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(base_path('routes/crudforge-products.php'));

        $this->assertStringContainsString('Route::apiResource', $content);
        $this->assertStringContainsString("'products'", $content);
        $this->assertStringContainsString('ProductController', $content);
    }

    public function test_migration_is_not_duplicated_on_regeneration(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $countAfterFirst = count(glob(database_path('migrations/*_create_products_table.php')) ?: []);

        $this->artisan('crudforge:generate Product --fields=name:string,body:text --force')
            ->assertSuccessful();

        $countAfterSecond = count(glob(database_path('migrations/*_create_products_table.php')) ?: []);

        $this->assertSame($countAfterFirst, $countAfterSecond, 'Regeneration must not create a duplicate migration');
    }

    public function test_command_fails_with_numeric_module_name(): void
    {
        $this->artisan('crudforge:generate 123 --fields=name:string --force')
            ->assertFailed();
    }

    public function test_command_fails_with_unsafe_module_name(): void
    {
        $this->artisan('crudforge:generate "Product;drop" --fields=name:string --force')
            ->assertFailed();
    }

    public function test_command_fails_without_fields_option(): void
    {
        $this->artisan('crudforge:generate Product --force')
            ->assertFailed();
    }

    public function test_command_fails_with_unsupported_field_type(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:blob --force')
            ->assertFailed();
    }

    public function test_generated_test_covers_all_crud_endpoints(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --force')
            ->assertSuccessful();

        $content = File::get(base_path('tests/Feature/ProductApiTest.php'));

        $this->assertStringContainsString('test_index_returns_paginated_list', $content);
        $this->assertStringContainsString('test_store_creates_resource', $content);
        $this->assertStringContainsString('test_show_returns_single_resource', $content);
        $this->assertStringContainsString('test_update_modifies_resource', $content);
        $this->assertStringContainsString('test_destroy_deletes_resource', $content);
    }

    public function test_dry_run_does_not_write_files(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string --dry-run')
            ->assertSuccessful();

        foreach ($this->generatedPaths as $path) {
            $this->assertFileDoesNotExist($path, "Dry-run must not write: {$path}");
        }
    }

    public function test_guard_safe_path_rejects_path_traversal(): void
    {
        $command = $this->app->make(GenerateCrudCommand::class);
        $method  = new \ReflectionMethod($command, 'guardSafePath');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsafe output path/');

        $method->invoke($command, base_path() . '/../etc/passwd');
    }

    public function test_guard_safe_path_rejects_dotdot_in_path(): void
    {
        $command = $this->app->make(GenerateCrudCommand::class);
        $method  = new \ReflectionMethod($command, 'guardSafePath');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);

        $method->invoke($command, base_path('Models/../../../etc/passwd'));
    }
}

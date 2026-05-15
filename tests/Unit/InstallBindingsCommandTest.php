<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use Illuminate\Support\Facades\File;
use MuhammedSalama\CrudForge\Tests\TestCase;

final class InstallBindingsCommandTest extends TestCase
{
    private string $registryPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registryPath = base_path('bootstrap/crudforge-bindings.php');

        // Ensure a clean slate regardless of test execution order.
        File::delete($this->registryPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->registryPath);
        File::deleteDirectory(app_path('Interfaces'));
        File::deleteDirectory(app_path('Repositories'));

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Registry creation
    // -------------------------------------------------------------------------

    public function test_it_creates_registry_file_for_single_model(): void
    {
        $this->artisan('crudforge:install-bindings Product')
            ->assertSuccessful();

        $this->assertFileExists($this->registryPath);
    }

    public function test_registry_file_contains_correct_class_references(): void
    {
        $this->artisan('crudforge:install-bindings Product')
            ->assertSuccessful();

        $content = File::get($this->registryPath);

        $this->assertStringContainsString('App\\Interfaces\\ProductRepositoryInterface::class', $content);
        $this->assertStringContainsString('App\\Repositories\\ProductRepository::class', $content);
    }

    public function test_registry_file_returns_valid_php_array(): void
    {
        $this->artisan('crudforge:install-bindings Product')
            ->assertSuccessful();

        /** @var mixed $result */
        $result = require $this->registryPath;

        $this->assertIsArray($result);
    }

    public function test_registry_array_maps_interface_to_concrete(): void
    {
        $this->artisan('crudforge:install-bindings Product')
            ->assertSuccessful();

        /** @var array<string, string> $bindings */
        $bindings = require $this->registryPath;

        $this->assertArrayHasKey('App\\Interfaces\\ProductRepositoryInterface', $bindings);
        $this->assertSame(
            'App\\Repositories\\ProductRepository',
            $bindings['App\\Interfaces\\ProductRepositoryInterface']
        );
    }

    // -------------------------------------------------------------------------
    // Idempotency
    // -------------------------------------------------------------------------

    public function test_it_does_not_duplicate_existing_bindings(): void
    {
        $this->artisan('crudforge:install-bindings Product')->assertSuccessful();
        $this->artisan('crudforge:install-bindings Product')->assertSuccessful();

        $content = File::get($this->registryPath);

        $this->assertSame(
            1,
            substr_count($content, 'App\\Interfaces\\ProductRepositoryInterface::class')
        );
    }

    // -------------------------------------------------------------------------
    // Merge across multiple models
    // -------------------------------------------------------------------------

    public function test_it_merges_bindings_from_multiple_models(): void
    {
        $this->artisan('crudforge:install-bindings Product')->assertSuccessful();
        $this->artisan('crudforge:install-bindings Order')->assertSuccessful();

        /** @var array<string, string> $bindings */
        $bindings = require $this->registryPath;

        $this->assertCount(2, $bindings);
        $this->assertArrayHasKey('App\\Interfaces\\ProductRepositoryInterface', $bindings);
        $this->assertArrayHasKey('App\\Interfaces\\OrderRepositoryInterface', $bindings);
    }

    // -------------------------------------------------------------------------
    // --all detection
    // -------------------------------------------------------------------------

    public function test_it_detects_all_repository_bindings(): void
    {
        File::ensureDirectoryExists(app_path('Interfaces'));
        File::ensureDirectoryExists(app_path('Repositories'));
        File::put(
            app_path('Interfaces/ProductRepositoryInterface.php'),
            '<?php interface ProductRepositoryInterface {}'
        );
        File::put(
            app_path('Repositories/ProductRepository.php'),
            '<?php class ProductRepository {}'
        );

        $this->artisan('crudforge:install-bindings --all')
            ->assertSuccessful();

        $this->assertFileExists($this->registryPath);

        $content = File::get($this->registryPath);

        $this->assertStringContainsString('App\\Interfaces\\ProductRepositoryInterface::class', $content);
        $this->assertStringContainsString('App\\Repositories\\ProductRepository::class', $content);
    }

    public function test_all_flag_returns_failure_when_no_interfaces_found(): void
    {
        $this->artisan('crudforge:install-bindings --all')
            ->assertFailed();
    }

    // -------------------------------------------------------------------------
    // Input validation
    // -------------------------------------------------------------------------

    public function test_it_fails_without_name_or_all_flag(): void
    {
        $this->artisan('crudforge:install-bindings')
            ->assertFailed();
    }
}

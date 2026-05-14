<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use Illuminate\Support\Facades\File;
use MuhammedSalama\CrudForge\Tests\TestCase;

final class InstallBindingsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(app_path('Providers/CrudForgeGeneratedServiceProvider.php'));
        File::delete(app_path('Interfaces/ProductRepositoryInterface.php'));
        File::delete(app_path('Repositories/ProductRepository.php'));
        File::deleteDirectory(app_path('Interfaces'));
        File::deleteDirectory(app_path('Repositories'));

        parent::tearDown();
    }

    public function test_it_creates_generated_service_provider_for_single_model(): void
    {
        $this->artisan('crudforge:install-bindings Product --force')
            ->assertSuccessful();

        $path = app_path('Providers/CrudForgeGeneratedServiceProvider.php');

        $this->assertFileExists($path);

        $content = File::get($path);

        $this->assertStringContainsString('namespace App\\Providers;', $content);
        $this->assertStringContainsString('\\App\\Interfaces\\ProductRepositoryInterface::class', $content);
        $this->assertStringContainsString('\\App\\Repositories\\ProductRepository::class', $content);
    }

    public function test_it_does_not_duplicate_existing_bindings(): void
    {
        $this->artisan('crudforge:install-bindings Product --force')
            ->assertSuccessful();

        $this->artisan('crudforge:install-bindings Product --force')
            ->assertSuccessful();

        $content = File::get(app_path('Providers/CrudForgeGeneratedServiceProvider.php'));

        $this->assertSame(1, substr_count($content, '\\App\\Interfaces\\ProductRepositoryInterface::class'));
    }

    public function test_it_detects_all_repository_bindings(): void
    {
        File::ensureDirectoryExists(app_path('Interfaces'));
        File::ensureDirectoryExists(app_path('Repositories'));
        File::put(app_path('Interfaces/ProductRepositoryInterface.php'), '<?php interface ProductRepositoryInterface {}');
        File::put(app_path('Repositories/ProductRepository.php'), '<?php class ProductRepository {}');

        $this->artisan('crudforge:install-bindings --all --force')
            ->assertSuccessful();

        $content = File::get(app_path('Providers/CrudForgeGeneratedServiceProvider.php'));

        $this->assertStringContainsString('\\App\\Interfaces\\ProductRepositoryInterface::class', $content);
        $this->assertStringContainsString('\\App\\Repositories\\ProductRepository::class', $content);
    }
}

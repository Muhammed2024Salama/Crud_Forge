<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Tests\TestCase;

final class GenerateCrudCommandTest extends TestCase
{
    public function test_command_generates_product_crud_files(): void
    {
        $this->artisan('crudforge:generate Product --fields=name:string,price:decimal,status:boolean --force')
            ->assertSuccessful();

        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileExists(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertFileExists(app_path('Interfaces/ProductRepositoryInterface.php'));
        $this->assertFileExists(app_path('Repositories/ProductRepository.php'));
        $this->assertFileExists(app_path('Services/ProductService.php'));
        $this->assertFileExists(app_path('Http/Requests/StoreProductRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/UpdateProductRequest.php'));
        $this->assertFileExists(app_path('Http/Resources/ProductResource.php'));
        $this->assertFileExists(base_path('routes/crudforge-products.php'));
    }
}

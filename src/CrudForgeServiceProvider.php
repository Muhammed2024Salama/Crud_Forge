<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge;

use MuhammedSalama\CrudForge\Console\Commands\GenerateCrudCommand;
use MuhammedSalama\CrudForge\Console\Commands\InstallBindingsCommand;
use MuhammedSalama\CrudForge\Console\Commands\InstallCommand;
use MuhammedSalama\CrudForge\Contracts\StubRendererContract;
use MuhammedSalama\CrudForge\Generators\ControllerGenerator;
use MuhammedSalama\CrudForge\Generators\FactoryGenerator;
use MuhammedSalama\CrudForge\Generators\GeneratorOrchestrator;
use MuhammedSalama\CrudForge\Generators\InterfaceGenerator;
use MuhammedSalama\CrudForge\Generators\MigrationGenerator;
use MuhammedSalama\CrudForge\Generators\ModelGenerator;
use MuhammedSalama\CrudForge\Generators\RepositoryGenerator;
use MuhammedSalama\CrudForge\Generators\RequestGenerator;
use MuhammedSalama\CrudForge\Generators\ResourceGenerator;
use MuhammedSalama\CrudForge\Generators\RouteSnippetGenerator;
use MuhammedSalama\CrudForge\Generators\ServiceGenerator;
use MuhammedSalama\CrudForge\Generators\TestGenerator;
use MuhammedSalama\CrudForge\Generators\TranslationGenerator;
use MuhammedSalama\CrudForge\Support\StubRenderer\StubRenderer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class CrudForgeServiceProvider extends ServiceProvider
{
    /**
     * Built-in generator classes, in generation order.
     * Third-party packages extend the pipeline by tagging their own
     * GeneratorContract implementations with 'crudforge.generators'.
     */
    private const CORE_GENERATORS = [
        ModelGenerator::class,
        ControllerGenerator::class,
        ServiceGenerator::class,
        RepositoryGenerator::class,
        InterfaceGenerator::class,
        RequestGenerator::class,
        ResourceGenerator::class,
        MigrationGenerator::class,
        FactoryGenerator::class,
        TestGenerator::class,
        TranslationGenerator::class,
        RouteSnippetGenerator::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/crudforge.php', 'crudforge');

        $this->app->singleton(StubRendererContract::class, StubRenderer::class);

        foreach (self::CORE_GENERATORS as $class) {
            $this->app->bind($class);
        }

        $this->app->tag(self::CORE_GENERATORS, 'crudforge.generators');

        $this->app->singleton(GeneratorOrchestrator::class, static function (Application $app): GeneratorOrchestrator {
            return new GeneratorOrchestrator($app->tagged('crudforge.generators'));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/crudforge.php' => config_path('crudforge.php'),
        ], 'crudforge-config');

        $this->publishes([
            __DIR__ . '/Stubs' => base_path('stubs/vendor/crudforge'),
        ], 'crudforge-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCrudCommand::class,
                InstallBindingsCommand::class,
                InstallCommand::class,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge;

use MuhammedSalama\CrudForge\Console\Commands\GenerateCrudCommand;
use MuhammedSalama\CrudForge\Console\Commands\InstallBindingsCommand;
use MuhammedSalama\CrudForge\Support\StubRenderer\StubRenderer;
use Illuminate\Support\ServiceProvider;

final class CrudForgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/crudforge.php', 'crudforge');

        $this->app->singleton(StubRenderer::class, static fn (): StubRenderer => new StubRenderer());
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
            ]);
        }
    }
}

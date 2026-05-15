<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Hydrates the application at runtime from two CrudForge-owned registry files:
 *
 *   bootstrap/crudforge-bindings.php  — repository interface → concrete bindings
 *   routes/crudforge.php              — route manifest (one require per module)
 *
 * Both files are generated and maintained by crudforge:generate / crudforge:install-bindings.
 * Neither file is a Laravel core file; no changes to bootstrap/providers.php are ever made.
 */
final class CrudForgeRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->hydrateBindings();
    }

    public function boot(): void
    {
        $this->hydrateRoutes();
    }

    private function hydrateBindings(): void
    {
        $registry = base_path('bootstrap/crudforge-bindings.php');

        if (! file_exists($registry)) {
            return;
        }

        /** @var mixed $bindings */
        $bindings = require $registry;

        if (! is_array($bindings)) {
            return;
        }

        foreach ($bindings as $abstract => $concrete) {
            if (is_string($abstract) && is_string($concrete)) {
                $this->app->bind($abstract, $concrete);
            }
        }
    }

    private function hydrateRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $registry = base_path('routes/crudforge.php');

        if (! file_exists($registry)) {
            return;
        }

        $prefix = (string) config('crudforge.defaults.route_prefix', 'api');

        Route::middleware('api')
            ->prefix($prefix)
            ->group($registry);
    }
}

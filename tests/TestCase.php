<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests;

use MuhammedSalama\CrudForge\CrudForgeRuntimeServiceProvider;
use MuhammedSalama\CrudForge\CrudForgeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CrudForgeServiceProvider::class,
            CrudForgeRuntimeServiceProvider::class,
        ];
    }
}

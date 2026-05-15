<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Output paths
    |--------------------------------------------------------------------------
    | Absolute paths where generated files are written.
    | All defaults follow standard Laravel project layout.
    */
    'paths' => [
        'models'       => app_path('Models'),
        'controllers'  => app_path('Http/Controllers/Api'),
        'requests'     => app_path('Http/Requests'),
        'resources'    => app_path('Http/Resources'),
        'interfaces'   => app_path('Interfaces'),
        'repositories' => app_path('Repositories'),
        'services'     => app_path('Services'),
        'tests'        => base_path('tests/Feature'),
        'lang'         => lang_path(),
        'routes'       => base_path('routes'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PHP namespaces
    |--------------------------------------------------------------------------
    | Namespaces written into generated class files.
    | Must match the 'paths' section above.
    */
    'namespaces' => [
        'models'       => 'App\\Models',
        'controllers'  => 'App\\Http\\Controllers\\Api',
        'requests'     => 'App\\Http\\Requests',
        'resources'    => 'App\\Http\\Resources',
        'interfaces'   => 'App\\Interfaces',
        'repositories' => 'App\\Repositories',
        'services'     => 'App\\Services',
        'tests'        => 'Tests\\Feature',
    ],

    /*
    |--------------------------------------------------------------------------
    | Base controller
    |--------------------------------------------------------------------------
    */
    'base_controller' => 'App\\Http\\Controllers\\Controller',

    /*
    |--------------------------------------------------------------------------
    | Runtime defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'pagination_per_page' => 15,

        /*
         * URL prefix applied to auto-loaded CrudForge routes.
         * Change to 'v1' or '' as needed.
         */
        'route_prefix' => 'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-register
    |--------------------------------------------------------------------------
    | When true, `crudforge:generate` automatically updates the two
    | CrudForge-owned registry files after each generation:
    |
    |   bootstrap/crudforge-bindings.php  — repository interface bindings
    |   routes/crudforge.php              — route manifest
    |
    | These files are CrudForge-owned (not Laravel core files) and are safe
    | to commit to version control.
    |
    | Set to false in read-only or locked deployments where filesystem writes
    | during Artisan commands are undesirable. The generate command will print
    | setup instructions instead.
    */
    'auto_register' => true,
];

<?php

declare(strict_types=1);

return [
    'paths' => [
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers/Api'),
        'requests' => app_path('Http/Requests'),
        'resources' => app_path('Http/Resources'),
        'interfaces' => app_path('Interfaces'),
        'repositories' => app_path('Repositories'),
        'services' => app_path('Services'),
        'tests' => base_path('tests/Feature'),
        'lang' => lang_path(),
        'routes' => base_path('routes'),
    ],

    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers\\Api',
        'requests' => 'App\\Http\\Requests',
        'resources' => 'App\\Http\\Resources',
        'interfaces' => 'App\\Interfaces',
        'repositories' => 'App\\Repositories',
        'services' => 'App\\Services',
        'tests' => 'Tests\\Feature',
    ],

    'defaults' => [
        'pagination_per_page' => 15,
        'route_prefix' => 'api',
    ],
];

<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators\Concerns;

use Illuminate\Support\Str;

trait BuildsCrudContext
{
    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, string>
     */
    protected function buildContext(string $name, array $fields): array
    {
        $model        = Str::studly(Str::singular($name));
        $modelVariable = Str::camel($model);
        $table        = Str::snake(Str::pluralStudly($model));
        $route        = Str::kebab(Str::pluralStudly($model));
        $translation  = Str::snake(Str::pluralStudly($model));

        $fillable = collect($fields)
            ->pluck('name')
            ->map(fn (string $field): string => "        '{$field}',")
            ->implode(PHP_EOL);

        $storeRules = collect($fields)
            ->map(fn (array $field): string => "            '{$field['name']}' => [{$field['validation']}],")
            ->implode(PHP_EOL);

        $updateRules = collect($fields)
            ->map(fn (array $field): string => "            '{$field['name']}' => [{$field['updateValidation']}],")
            ->implode(PHP_EOL);

        $migrationFields = collect($fields)
            ->pluck('migration')
            ->map(fn (string $line): string => '            ' . $line)
            ->implode(PHP_EOL);

        $factoryFields = collect($fields)
            ->pluck('factory')
            ->map(fn (string $line): string => '            ' . $line)
            ->implode(PHP_EOL);

        $resourceFields = collect($fields)
            ->map(fn (array $field): string => "            '{$field['name']}' => \$this->{$field['name']},")
            ->implode(PHP_EOL);

        $castLines = collect($fields)
            ->pluck('castEntry')
            ->filter(fn (string $entry): bool => $entry !== '')
            ->implode(PHP_EOL);

        $castsBlock = $castLines !== ''
            ? "\n    protected \$casts = [\n{$castLines}\n    ];\n"
            : '';

        $searchableFields = collect($fields)
            ->filter(fn (array $field): bool => (bool) $field['searchable'])
            ->pluck('name')
            ->map(fn (string $field): string => "'{$field}'")
            ->implode(', ');

        $sortableFields = collect($fields)
            ->filter(fn (array $field): bool => (bool) $field['sortable'])
            ->pluck('name')
            ->push('id')
            ->push('created_at')
            ->unique()
            ->map(fn (string $field): string => "'{$field}'")
            ->implode(', ');

        return [
            'model'          => $model,
            'modelVariable'  => $modelVariable,
            'modelPlural'    => Str::pluralStudly($model),
            'table'          => $table,
            'route'          => $route,
            'translation'    => $translation,
            'fillable'       => $fillable,
            'rules'          => $storeRules,
            'updateRules'    => $updateRules,
            'migrationFields'=> $migrationFields,
            'factoryFields'  => $factoryFields,
            'resourceFields' => $resourceFields,
            'castsBlock'     => $castsBlock,
            'searchableFields' => $searchableFields,
            'sortableFields'   => $sortableFields,
            // Namespace context — read from published config, falls back to Laravel conventions.
            'nsModels'          => $this->resolveNamespace('models', 'App\\Models'),
            'nsControllers'     => $this->resolveNamespace('controllers', 'App\\Http\\Controllers\\Api'),
            'nsRequests'        => $this->resolveNamespace('requests', 'App\\Http\\Requests'),
            'nsResources'       => $this->resolveNamespace('resources', 'App\\Http\\Resources'),
            'nsInterfaces'      => $this->resolveNamespace('interfaces', 'App\\Interfaces'),
            'nsRepositories'    => $this->resolveNamespace('repositories', 'App\\Repositories'),
            'nsServices'        => $this->resolveNamespace('services', 'App\\Services'),
            'nsTests'           => $this->resolveNamespace('tests', 'Tests\\Feature'),
            'nsBaseController'  => rtrim((string) config('crudforge.base_controller', 'App\\Http\\Controllers\\Controller'), '\\'),
        ];
    }

    private function resolveNamespace(string $key, string $default): string
    {
        return rtrim((string) config("crudforge.namespaces.{$key}", $default), '\\');
    }
}

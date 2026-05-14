<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators\Concerns;

use Illuminate\Support\Str;

trait BuildsCrudContext
{
    /**
     * @param array<int, array<string, mixed>> $fields
     * @return array<string, string>
     */
    protected function buildContext(string $name, array $fields): array
    {
        $model = Str::studly(Str::singular($name));
        $modelVariable = Str::camel($model);
        $table = Str::snake(Str::pluralStudly($model));
        $route = Str::kebab(Str::pluralStudly($model));
        $translation = Str::snake(Str::pluralStudly($model));

        $fillable = collect($fields)
            ->pluck('name')
            ->map(fn (string $field): string => "        '{$field}',")
            ->implode(PHP_EOL);

        $rules = collect($fields)
            ->map(fn (array $field): string => "            '{$field['name']}' => [{$field['validation']}],")
            ->implode(PHP_EOL);

        $migrationFields = collect($fields)->pluck('migration')->map(fn (string $line): string => '            ' . $line)->implode(PHP_EOL);
        $factoryFields = collect($fields)->pluck('factory')->map(fn (string $line): string => '            ' . $line)->implode(PHP_EOL);

        $resourceFields = collect($fields)
            ->map(fn (array $field): string => "            '{$field['name']}' => \$this->{$field['name']},")
            ->implode(PHP_EOL);

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
            'model' => $model,
            'modelVariable' => $modelVariable,
            'modelPlural' => Str::pluralStudly($model),
            'table' => $table,
            'route' => $route,
            'translation' => $translation,
            'fillable' => $fillable,
            'rules' => $rules,
            'migrationFields' => $migrationFields,
            'factoryFields' => $factoryFields,
            'resourceFields' => $resourceFields,
            'searchableFields' => $searchableFields !== '' ? $searchableFields : "'id'",
            'sortableFields' => $sortableFields,
        ];
    }
}

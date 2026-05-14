<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Support\Fields;

use InvalidArgumentException;

final class FieldParser
{
    /**
     * @return array<int, array{name: string, type: string, validation: string, migration: string, factory: string, searchable: bool, sortable: bool}>
     */
    public function parse(?string $fields): array
    {
        if ($fields === null || trim($fields) === '') {
            throw new InvalidArgumentException('The --fields option is required. Example: --fields="name:string,price:decimal,status:boolean"');
        }

        $parsed = [];
        $items = array_filter(array_map('trim', explode(',', $fields)));

        foreach ($items as $item) {
            [$name, $type] = array_pad(array_map('trim', explode(':', $item, 2)), 2, null);

            if (! is_string($name) || ! is_string($type) || $name === '' || $type === '') {
                throw new InvalidArgumentException("Invalid field definition: {$item}");
            }

            $this->guardSafeName($name);

            $normalizedType = $this->normalizeType($type);

            $parsed[] = [
                'name' => $name,
                'type' => $normalizedType,
                'validation' => $this->validationFor($normalizedType),
                'migration' => $this->migrationFor($name, $normalizedType),
                'factory' => $this->factoryFor($name, $normalizedType),
                'searchable' => in_array($normalizedType, ['string', 'text'], true),
                'sortable' => in_array($normalizedType, ['string', 'integer', 'decimal', 'boolean', 'date', 'datetime'], true),
            ];
        }

        return $parsed;
    }

    private function guardSafeName(string $name): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Unsafe field name: {$name}");
        }
    }

    private function normalizeType(string $type): string
    {
        return match (strtolower($type)) {
            'str', 'varchar' => 'string',
            'int' => 'integer',
            'bool' => 'boolean',
            'float', 'double' => 'decimal',
            'foreignid', 'foreign_id' => 'foreignId',
            'date', 'datetime', 'text', 'json', 'decimal', 'boolean', 'integer', 'string' => strtolower($type) === 'foreignid' ? 'foreignId' : strtolower($type),
            default => throw new InvalidArgumentException("Unsupported field type: {$type}"),
        };
    }

    private function validationFor(string $type): string
    {
        return match ($type) {
            'string' => "'required', 'string', 'max:255'",
            'text' => "'nullable', 'string'",
            'integer', 'foreignId' => "'required', 'integer'",
            'decimal' => "'required', 'numeric', 'min:0'",
            'boolean' => "'required', 'boolean'",
            'date' => "'nullable', 'date'",
            'datetime' => "'nullable', 'date'",
            'json' => "'nullable', 'array'",
            default => "'nullable'",
        };
    }

    private function migrationFor(string $name, string $type): string
    {
        return match ($type) {
            'string' => "\$table->string('{$name}');",
            'text' => "\$table->text('{$name}')->nullable();",
            'integer' => "\$table->integer('{$name}');",
            'foreignId' => "\$table->foreignId('{$name}')->constrained()->cascadeOnDelete();",
            'decimal' => "\$table->decimal('{$name}', 10, 2);",
            'boolean' => "\$table->boolean('{$name}')->default(false);",
            'date' => "\$table->date('{$name}')->nullable();",
            'datetime' => "\$table->dateTime('{$name}')->nullable();",
            'json' => "\$table->json('{$name}')->nullable();",
            default => "\$table->string('{$name}')->nullable();",
        };
    }

    private function factoryFor(string $name, string $type): string
    {
        return match ($type) {
            'string' => "'{$name}' => fake()->words(2, true),",
            'text' => "'{$name}' => fake()->paragraph(),",
            'integer' => "'{$name}' => fake()->numberBetween(1, 100),",
            'foreignId' => "'{$name}' => 1,",
            'decimal' => "'{$name}' => fake()->randomFloat(2, 1, 999),",
            'boolean' => "'{$name}' => fake()->boolean(),",
            'date' => "'{$name}' => fake()->date(),",
            'datetime' => "'{$name}' => fake()->dateTime(),",
            'json' => "'{$name}' => ['sample' => true],",
            default => "'{$name}' => null,",
        };
    }
}

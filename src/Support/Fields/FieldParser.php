<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Support\Fields;

use InvalidArgumentException;

final class FieldParser
{
    /**
     * @return array<int, array{name: string, type: string, validation: string, updateValidation: string, migration: string, factory: string, castEntry: string, searchable: bool, sortable: bool}>
     */
    public function parse(?string $fields): array
    {
        if ($fields === null || trim($fields) === '') {
            throw new InvalidArgumentException(
                'The --fields option is required. Example: --fields="name:string,price:decimal,status:boolean"'
            );
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
            $validation = $this->validationFor($normalizedType);

            $parsed[] = [
                'name'             => $name,
                'type'             => $normalizedType,
                'validation'       => $validation,
                'updateValidation' => $this->toUpdateValidation($validation),
                'migration'        => $this->migrationFor($name, $normalizedType),
                'factory'          => $this->factoryFor($name, $normalizedType),
                'castEntry'        => $this->castEntryFor($name, $normalizedType),
                'searchable'       => in_array($normalizedType, ['string', 'text'], true),
                'sortable'         => in_array($normalizedType, ['string', 'integer', 'bigInteger', 'unsignedInteger', 'unsignedBigInteger', 'decimal', 'boolean', 'date', 'datetime', 'timestamp'], true),
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
            'str', 'varchar', 'string'                    => 'string',
            'int', 'integer'                              => 'integer',
            'bigint', 'biginteger'                        => 'bigInteger',
            'uint', 'unsignedinteger', 'unsigned_integer' => 'unsignedInteger',
            'ubigint', 'unsignedbiginteger', 'unsigned_big_integer' => 'unsignedBigInteger',
            'bool', 'boolean'                             => 'boolean',
            'float', 'double', 'decimal'                  => 'decimal',
            'foreignid', 'foreign_id'                     => 'foreignId',
            'text'                                        => 'text',
            'json', 'jsonb'                               => 'json',
            'date'                                        => 'date',
            'datetime'                                    => 'datetime',
            'timestamp'                                   => 'timestamp',
            default => throw new InvalidArgumentException("Unsupported field type: {$type}"),
        };
    }

    private function validationFor(string $type): string
    {
        return match ($type) {
            'string'                                        => "'required', 'string', 'max:255'",
            'text'                                          => "'nullable', 'string'",
            'integer', 'bigInteger', 'unsignedInteger',
            'unsignedBigInteger', 'foreignId'               => "'required', 'integer'",
            'decimal'                                       => "'required', 'numeric', 'min:0'",
            'boolean'                                       => "'required', 'boolean'",
            'date', 'datetime', 'timestamp'                 => "'nullable', 'date'",
            'json'                                          => "'nullable', 'array'",
            default                                         => "'nullable'",
        };
    }

    private function toUpdateValidation(string $storeValidation): string
    {
        return str_replace("'required'", "'sometimes'", $storeValidation);
    }

    private function migrationFor(string $name, string $type): string
    {
        return match ($type) {
            'string'            => "\$table->string('{$name}');",
            'text'              => "\$table->text('{$name}')->nullable();",
            'integer'           => "\$table->integer('{$name}');",
            'bigInteger'        => "\$table->bigInteger('{$name}');",
            'unsignedInteger'   => "\$table->unsignedInteger('{$name}');",
            'unsignedBigInteger'=> "\$table->unsignedBigInteger('{$name}');",
            'foreignId'         => "\$table->foreignId('{$name}')->constrained()->cascadeOnDelete();",
            'decimal'           => "\$table->decimal('{$name}', 10, 2);",
            'boolean'           => "\$table->boolean('{$name}')->default(false);",
            'date'              => "\$table->date('{$name}')->nullable();",
            'datetime'          => "\$table->dateTime('{$name}')->nullable();",
            'timestamp'         => "\$table->timestamp('{$name}')->nullable();",
            'json'              => "\$table->json('{$name}')->nullable();",
            default             => "\$table->string('{$name}')->nullable();",
        };
    }

    private function factoryFor(string $name, string $type): string
    {
        return match ($type) {
            'string'                                     => "'{$name}' => fake()->words(2, true),",
            'text'                                       => "'{$name}' => fake()->paragraph(),",
            'integer', 'unsignedInteger'                 => "'{$name}' => fake()->numberBetween(1, 100),",
            'bigInteger', 'unsignedBigInteger'           => "'{$name}' => fake()->numberBetween(1, 9999),",
            'foreignId'                                  => "'{$name}' => 1,",
            'decimal'                                    => "'{$name}' => fake()->randomFloat(2, 1, 999),",
            'boolean'                                    => "'{$name}' => fake()->boolean(),",
            'date'                                       => "'{$name}' => fake()->date(),",
            'datetime', 'timestamp'                      => "'{$name}' => fake()->dateTime(),",
            'json'                                       => "'{$name}' => ['sample' => true],",
            default                                      => "'{$name}' => null,",
        };
    }

    private function castEntryFor(string $name, string $type): string
    {
        return match ($type) {
            'boolean'           => "        '{$name}' => 'boolean',",
            'date'              => "        '{$name}' => 'date',",
            'datetime'          => "        '{$name}' => 'datetime',",
            'timestamp'         => "        '{$name}' => 'datetime',",
            'decimal'           => "        '{$name}' => 'decimal:2',",
            'json'              => "        '{$name}' => 'array',",
            default             => '',
        };
    }
}

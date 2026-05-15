<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Contracts;

interface GeneratorContract
{
    /**
     * Generate one or more output files for the given model and fields.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return non-empty-list<array{path: string, content: string}>
     */
    public function generate(string $name, array $fields): array;
}

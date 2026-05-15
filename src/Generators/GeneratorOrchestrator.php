<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

use MuhammedSalama\CrudForge\Contracts\GeneratorContract;

/**
 * Iterates all registered generators and collects every output file into a flat list.
 * Generators are tagged as 'crudforge.generators' in the service provider so third-party
 * packages can extend the generation pipeline without modifying core files.
 */
final class GeneratorOrchestrator
{
    /** @param iterable<GeneratorContract> $generators */
    public function __construct(private readonly iterable $generators) {}

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return list<array{path: string, content: string}>
     */
    public function generate(string $name, array $fields): array
    {
        $files = [];

        foreach ($this->generators as $generator) {
            foreach ($generator->generate($name, $fields) as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }
}

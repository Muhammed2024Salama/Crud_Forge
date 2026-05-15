<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

use MuhammedSalama\CrudForge\Contracts\GeneratorContract;
use MuhammedSalama\CrudForge\Contracts\StubRendererContract;
use MuhammedSalama\CrudForge\Generators\Concerns\BuildsCrudContext;
use Illuminate\Filesystem\Filesystem;

abstract class AbstractGenerator implements GeneratorContract
{
    use BuildsCrudContext;

    public function __construct(
        protected readonly Filesystem $files,
        protected readonly StubRendererContract $renderer,
    ) {}

    protected function stubPath(string $stub): string
    {
        $published = base_path("stubs/vendor/crudforge/{$stub}.stub");

        if ($this->files->exists($published)) {
            return $published;
        }

        return __DIR__ . "/../Stubs/{$stub}.stub";
    }

    /**
     * @param array<string, string> $context
     */
    protected function render(string $stub, array $context): string
    {
        return $this->renderer->render($this->stubPath($stub), $context);
    }

    /**
     * Resolve an output path from config, falling back to the given default.
     * Strips any trailing directory separator so callers can safely append DIRECTORY_SEPARATOR.
     */
    protected function outputPath(string $configKey, string $default): string
    {
        return rtrim((string) config("crudforge.paths.{$configKey}", $default), '/\\');
    }
}

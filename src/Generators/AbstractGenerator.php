<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

use MuhammedSalama\CrudForge\Generators\Concerns\BuildsCrudContext;
use MuhammedSalama\CrudForge\Support\StubRenderer\StubRenderer;
use Illuminate\Filesystem\Filesystem;

abstract class AbstractGenerator
{
    use BuildsCrudContext;

    public function __construct(
        protected readonly Filesystem $files,
        protected readonly StubRenderer $renderer,
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
}

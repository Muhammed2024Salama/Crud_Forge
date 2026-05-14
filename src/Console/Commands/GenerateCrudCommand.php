<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

use MuhammedSalama\CrudForge\Generators\ControllerGenerator;
use MuhammedSalama\CrudForge\Generators\FactoryGenerator;
use MuhammedSalama\CrudForge\Generators\InterfaceGenerator;
use MuhammedSalama\CrudForge\Generators\MigrationGenerator;
use MuhammedSalama\CrudForge\Generators\ModelGenerator;
use MuhammedSalama\CrudForge\Generators\RepositoryGenerator;
use MuhammedSalama\CrudForge\Generators\RequestGenerator;
use MuhammedSalama\CrudForge\Generators\ResourceGenerator;
use MuhammedSalama\CrudForge\Generators\RouteSnippetGenerator;
use MuhammedSalama\CrudForge\Generators\ServiceGenerator;
use MuhammedSalama\CrudForge\Generators\TestGenerator;
use MuhammedSalama\CrudForge\Generators\TranslationGenerator;
use MuhammedSalama\CrudForge\Support\Fields\FieldParser;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Throwable;

final class GenerateCrudCommand extends Command
{
    protected $signature = 'crudforge:generate {name : The model/module name, e.g. Product} {--fields= : Comma-separated fields, e.g. name:string,price:decimal,status:boolean} {--force : Overwrite existing files without confirmation}';

    protected $description = 'Generate a production-ready Laravel CRUD module using CrudForge clean architecture stubs.';

    public function __construct(
        private readonly FieldParser $fieldParser,
        private readonly Filesystem $files,
        private readonly ModelGenerator $modelGenerator,
        private readonly ControllerGenerator $controllerGenerator,
        private readonly ServiceGenerator $serviceGenerator,
        private readonly RepositoryGenerator $repositoryGenerator,
        private readonly InterfaceGenerator $interfaceGenerator,
        private readonly RequestGenerator $requestGenerator,
        private readonly ResourceGenerator $resourceGenerator,
        private readonly MigrationGenerator $migrationGenerator,
        private readonly FactoryGenerator $factoryGenerator,
        private readonly TestGenerator $testGenerator,
        private readonly TranslationGenerator $translationGenerator,
        private readonly RouteSnippetGenerator $routeSnippetGenerator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $fieldsOption = is_string($this->option('fields')) ? $this->option('fields') : null;

        try {
            $this->guardSafeModuleName($name);
            $fields = $this->fieldParser->parse($fieldsOption);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $generatedFiles = $this->collectGeneratedFiles($name, $fields);

        foreach ($generatedFiles as $file) {
            if (! $this->writeFile($file['path'], $file['content'])) {
                $this->warn('Generation cancelled. No further files were written.');

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('CrudForge module generated successfully.');
        $this->warn('Route snippet was generated separately. Copy it into routes/api.php when ready.');
        $this->warn('Bind the generated interface to repository in your AppServiceProvider or a dedicated provider.');

        return self::SUCCESS;
    }

    private function guardSafeModuleName(string $name): void
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Unsafe module name: {$name}");
        }
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @return array<int, array{path: string, content: string}>
     */
    private function collectGeneratedFiles(string $name, array $fields): array
    {
        $files = [
            $this->modelGenerator->generate($name, $fields),
            $this->controllerGenerator->generate($name, $fields),
            $this->serviceGenerator->generate($name, $fields),
            $this->repositoryGenerator->generate($name, $fields),
            $this->interfaceGenerator->generate($name, $fields),
            $this->resourceGenerator->generate($name, $fields),
            $this->migrationGenerator->generate($name, $fields),
            $this->factoryGenerator->generate($name, $fields),
            $this->testGenerator->generate($name, $fields),
            $this->routeSnippetGenerator->generate($name, $fields),
        ];

        foreach ($this->requestGenerator->generate($name, $fields) as $requestFile) {
            $files[] = $requestFile;
        }

        foreach ($this->translationGenerator->generate($name, $fields) as $translationFile) {
            $files[] = $translationFile;
        }

        return $files;
    }

    private function writeFile(string $path, string $content): bool
    {
        $this->guardSafePath($path);

        if ($this->files->exists($path) && ! $this->option('force')) {
            if (! $this->confirm("File already exists: {$path}. Overwrite?", false)) {
                return false;
            }
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);

        $this->line("<info>created/updated</info> {$path}");

        return true;
    }

    private function guardSafePath(string $path): void
    {
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException("Unsafe output path: {$path}");
        }
    }
}

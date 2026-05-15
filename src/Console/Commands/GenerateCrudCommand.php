<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

use MuhammedSalama\CrudForge\Generators\GeneratorOrchestrator;
use MuhammedSalama\CrudForge\Support\Fields\FieldParser;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

final class GenerateCrudCommand extends Command
{
    protected $signature = 'crudforge:generate
                            {name : The model/module name in PascalCase, e.g. Product or BlogPost}
                            {--fields= : Comma-separated name:type pairs, e.g. name:string,price:decimal,status:boolean}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Preview files that would be generated without writing to disk}';

    protected $description = 'Generate a production-ready Laravel CRUD module using CrudForge clean architecture stubs.';

    public function __construct(
        private readonly FieldParser $fieldParser,
        private readonly Filesystem $files,
        private readonly GeneratorOrchestrator $orchestrator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name         = (string) $this->argument('name');
        $fieldsOption = is_string($this->option('fields')) ? $this->option('fields') : null;
        $isDryRun     = (bool) $this->option('dry-run');

        try {
            $this->guardSafeModuleName($name);
            $fields = $this->fieldParser->parse($fieldsOption);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $generatedFiles = $this->orchestrator->generate($name, $fields);

        if ($isDryRun) {
            $this->info("[dry-run] The following files would be generated for module \"{$name}\":");
            $this->newLine();

            foreach ($generatedFiles as $file) {
                $exists = $this->files->exists($file['path']) ? '<comment>[exists]</comment> ' : '<info>[new]</info>    ';
                $this->line("  {$exists} {$file['path']}");
            }

            $this->newLine();
            $this->line('<comment>Dry run complete — no files were written. Remove --dry-run to generate.</comment>');

            return self::SUCCESS;
        }

        $written = 0;
        $skipped = 0;

        foreach ($generatedFiles as $file) {
            $result = $this->writeFile($file['path'], $file['content']);

            match ($result) {
                WriteResult::Written => $written++,
                WriteResult::Skipped => $skipped++,
                WriteResult::Error   => $this->handleWriteError($file['path']),
            };

            if ($result === WriteResult::Error) {
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info("CrudForge module generated: {$written} file(s) written, {$skipped} skipped.");

        if ($skipped > 0) {
            $this->warn('Re-run with --force to overwrite all existing files.');
        }

        $this->warn('Route snippet generated in routes/. Require it from routes/api.php when ready.');
        $this->warn('Bind the interface → repository by running: php artisan crudforge:install-bindings ' . $name);

        return self::SUCCESS;
    }

    private function handleWriteError(string $path): void
    {
        $this->error("Failed to write: {$path}");
    }

    private function guardSafeModuleName(string $name): void
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            throw new InvalidArgumentException(
                "Invalid module name \"{$name}\". Use PascalCase letters and digits only (e.g. Product, BlogPost)."
            );
        }
    }

    private function writeFile(string $path, string $content): WriteResult
    {
        $this->guardSafePath($path);

        if ($this->files->exists($path) && ! $this->option('force')) {
            if (! $this->confirm("File already exists: {$path}. Overwrite?", false)) {
                $this->line("<comment>skipped</comment>  {$path}");

                return WriteResult::Skipped;
            }
        }

        $this->files->ensureDirectoryExists(dirname($path));

        if ($this->files->put($path, $content) === false) {
            return WriteResult::Error;
        }

        $this->line("<info>written</info>   {$path}");

        return WriteResult::Written;
    }

    private function guardSafePath(string $path): void
    {
        // Resolve the nearest existing ancestor directory to get a canonical path.
        $dir      = dirname($path);
        $resolved = realpath($dir) ?: realpath(dirname($dir));

        // base_path() with trailing separator prevents sibling-directory bypass:
        //   str_starts_with('/var/www/app_evil/x', '/var/www/app') → true (wrong!)
        //   str_starts_with('/var/www/app_evil/x', '/var/www/app/') → false (correct)
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (
            str_contains($path, '..')
            || ($resolved !== false && ! str_starts_with($resolved . DIRECTORY_SEPARATOR, $base))
            || ($resolved === false && ! str_starts_with($path, $base))
        ) {
            throw new InvalidArgumentException("Unsafe output path detected: {$path}");
        }
    }
}

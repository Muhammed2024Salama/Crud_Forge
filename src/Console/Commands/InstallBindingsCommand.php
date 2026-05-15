<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Writes interface → concrete repository pairs to the CrudForge binding registry.
 *
 * Target file: bootstrap/crudforge-bindings.php
 *
 * This file is owned by CrudForge and loaded at runtime by CrudForgeRuntimeServiceProvider.
 * bootstrap/providers.php is never modified.
 */
final class InstallBindingsCommand extends Command
{
    protected $signature = 'crudforge:install-bindings
                            {name? : The generated model name, e.g. Product}
                            {--all : Detect all *RepositoryInterface files and register bindings}';

    protected $description = 'Register repository bindings in bootstrap/crudforge-bindings.php.';

    private const REGISTRY_PATH = 'bootstrap/crudforge-bindings.php';

    public function handle(Filesystem $files): int
    {
        $useAll = (bool) $this->option('all');
        $name   = trim((string) $this->argument('name'));

        if (! $useAll && $name === '') {
            $this->error('Provide a model name (e.g. crudforge:install-bindings Product) or use --all.');

            return self::FAILURE;
        }

        $bindings = $useAll
            ? $this->detectBindings($files)
            : $this->bindingsForName($name);

        if ($bindings === []) {
            $this->error($useAll
                ? 'No matching interface/repository pairs found. Run crudforge:generate first.'
                : "No binding pair found for \"{$name}\". Run crudforge:generate {$name} first."
            );

            return self::FAILURE;
        }

        $this->writeRegistry($bindings, $files);

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Binding resolution
    // -------------------------------------------------------------------------

    /**
     * @return array<int, array{interface: string, repository: string}>
     */
    private function bindingsForName(string $name): array
    {
        $studly = Str::studly($name);

        $nsInterfaces   = rtrim((string) config('crudforge.namespaces.interfaces', 'App\\Interfaces'), '\\');
        $nsRepositories = rtrim((string) config('crudforge.namespaces.repositories', 'App\\Repositories'), '\\');

        return [[
            'interface'  => "{$nsInterfaces}\\{$studly}RepositoryInterface",
            'repository' => "{$nsRepositories}\\{$studly}Repository",
        ]];
    }

    /**
     * @return array<int, array{interface: string, repository: string}>
     */
    private function detectBindings(Filesystem $files): array
    {
        $interfacePath  = rtrim((string) config('crudforge.paths.interfaces', app_path('Interfaces')), '/\\');
        $repositoryPath = rtrim((string) config('crudforge.paths.repositories', app_path('Repositories')), '/\\');
        $nsInterfaces   = rtrim((string) config('crudforge.namespaces.interfaces', 'App\\Interfaces'), '\\');
        $nsRepositories = rtrim((string) config('crudforge.namespaces.repositories', 'App\\Repositories'), '\\');

        if (! $files->isDirectory($interfacePath) || ! $files->isDirectory($repositoryPath)) {
            return [];
        }

        $bindings = [];

        foreach ($files->files($interfacePath) as $file) {
            $filename = $file->getFilename();

            if (! Str::endsWith($filename, 'RepositoryInterface.php')) {
                continue;
            }

            $baseName = Str::before($filename, 'RepositoryInterface.php');

            if (! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $baseName)) {
                $this->warn("Skipping file with non-standard name: {$filename}");
                continue;
            }

            if (! $files->exists($repositoryPath . DIRECTORY_SEPARATOR . $baseName . 'Repository.php')) {
                continue;
            }

            $bindings[] = [
                'interface'  => "{$nsInterfaces}\\{$baseName}RepositoryInterface",
                'repository' => "{$nsRepositories}\\{$baseName}Repository",
            ];
        }

        return $bindings;
    }

    // -------------------------------------------------------------------------
    // Registry persistence
    // -------------------------------------------------------------------------

    /**
     * Merges $bindings into bootstrap/crudforge-bindings.php, creating the file
     * if it does not exist. The operation is idempotent — duplicate entries are
     * never written.
     *
     * @param array<int, array{interface: string, repository: string}> $bindings
     */
    private function writeRegistry(array $bindings, Filesystem $files): void
    {
        $registryPath = base_path(self::REGISTRY_PATH);

        $existing = $this->loadExistingRegistry($registryPath);

        $added = 0;

        foreach ($bindings as $entry) {
            $interface  = $entry['interface'];
            $repository = $entry['repository'];

            if (isset($existing[$interface])) {
                continue;
            }

            $existing[$interface] = $repository;
            $added++;
        }

        if ($added === 0) {
            $this->info('Binding registry is already up to date.');

            return;
        }

        $files->put($registryPath, $this->renderRegistry($existing));

        $this->info("Updated " . self::REGISTRY_PATH . " ({$added} binding(s) added).");
    }

    /**
     * Reads the registry file and returns its contents as a plain string-keyed map.
     * Returns an empty array if the file does not exist or is invalid.
     *
     * @return array<string, string>
     */
    private function loadExistingRegistry(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        /** @var mixed $result */
        $result = require $path;

        if (! is_array($result)) {
            return [];
        }

        $normalised = [];

        foreach ($result as $abstract => $concrete) {
            if (is_string($abstract) && is_string($concrete)) {
                $normalised[ltrim($abstract, '\\')] = ltrim($concrete, '\\');
            }
        }

        return $normalised;
    }

    /**
     * Renders the registry array as a PHP file.
     *
     * @param array<string, string> $bindings
     */
    private function renderRegistry(array $bindings): string
    {
        $lines = [
            '<?php',
            '',
            '// CrudForge binding registry.',
            '// Auto-generated by crudforge:install-bindings. Commit this file.',
            '',
            'return [',
        ];

        foreach ($bindings as $interface => $concrete) {
            $lines[] = "    \\{$interface}::class => \\{$concrete}::class,";
        }

        $lines[] = '];';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }
}

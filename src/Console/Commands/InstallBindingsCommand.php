<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class InstallBindingsCommand extends Command
{
    protected $signature = 'crudforge:install-bindings
                            {name? : The generated model name, e.g. Product}
                            {--all : Detect all *RepositoryInterface files and install matching bindings}
                            {--force : Write changes without confirmation}';

    protected $description = 'Create or update App\Providers\CrudForgeGeneratedServiceProvider with repository bindings.';

    private const PROVIDER_PATH = 'app/Providers/CrudForgeGeneratedServiceProvider.php';

    private const START_MARKER = '        // <crudforge-bindings>';

    private const END_MARKER = '        // </crudforge-bindings>';

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
            $message = $useAll
                ? 'No matching interface/repository pairs found. Run crudforge:generate first.'
                : "No binding pair found for \"{$name}\". Run crudforge:generate {$name} first.";

            $this->error($message);

            return self::FAILURE;
        }

        $providerPath  = base_path(self::PROVIDER_PATH);
        $providerExists = $files->exists($providerPath);

        if ($providerExists && ! $this->option('force')) {
            if (! $this->confirm('CrudForgeGeneratedServiceProvider already exists. Append missing bindings safely?', true)) {
                $this->warn('No changes were made.');

                return self::SUCCESS;
            }
        }

        if (! $providerExists) {
            $files->ensureDirectoryExists(dirname($providerPath));
            $files->put($providerPath, $this->newProviderContent($bindings));
            $this->info('Created ' . self::PROVIDER_PATH);
            $this->showRegistrationHint();

            return self::SUCCESS;
        }

        $existing = $files->get($providerPath);
        $updated  = $this->appendMissingBindings($existing, $bindings);

        if ($updated === $existing) {
            $this->info('No missing bindings found. Provider is already up to date.');

            return self::SUCCESS;
        }

        $files->put($providerPath, $updated);
        $this->info('Updated ' . self::PROVIDER_PATH);
        $this->showRegistrationHint();

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{interface: string, repository: string}>
     */
    private function bindingsForName(string $name): array
    {
        $studly = Str::studly($name);

        $nsInterfaces   = rtrim((string) config('crudforge.namespaces.interfaces', 'App\\Interfaces'), '\\');
        $nsRepositories = rtrim((string) config('crudforge.namespaces.repositories', 'App\\Repositories'), '\\');

        return [[
            'interface'  => "\\{$nsInterfaces}\\{$studly}RepositoryInterface::class",
            'repository' => "\\{$nsRepositories}\\{$studly}Repository::class",
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

            // Validate baseName is a safe PHP class name to prevent invalid provider generation.
            if (! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $baseName)) {
                $this->warn("Skipping file with non-standard name: {$filename}");
                continue;
            }

            $repositoryFile = $repositoryPath . DIRECTORY_SEPARATOR . $baseName . 'Repository.php';

            if (! $files->exists($repositoryFile)) {
                continue;
            }

            $bindings[] = [
                'interface'  => "\\{$nsInterfaces}\\{$baseName}RepositoryInterface::class",
                'repository' => "\\{$nsRepositories}\\{$baseName}Repository::class",
            ];
        }

        return $bindings;
    }

    /**
     * @param array<int, array{interface: string, repository: string}> $bindings
     */
    private function newProviderContent(array $bindings): string
    {
        $bindingLines = $this->bindingLines($bindings);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class CrudForgeGeneratedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
{$bindingLines}
    }
}
PHP;
    }

    /**
     * @param array<int, array{interface: string, repository: string}> $bindings
     */
    private function appendMissingBindings(string $content, array $bindings): string
    {
        $missing = array_values(array_filter(
            $bindings,
            static fn (array $b): bool => ! str_contains($content, $b['interface'])
        ));

        if ($missing === []) {
            return $content;
        }

        $newLines = $this->bindingLines($missing, includeMarkers: false);

        if (str_contains($content, self::START_MARKER) && str_contains($content, self::END_MARKER)) {
            return str_replace(
                self::END_MARKER,
                rtrim($newLines) . PHP_EOL . self::END_MARKER,
                $content
            );
        }

        $insertBlock = self::START_MARKER . PHP_EOL
            . rtrim($newLines) . PHP_EOL
            . self::END_MARKER . PHP_EOL;

        if (preg_match('/public function register\(\): void\s*\{/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1] + strlen($matches[0][0]);

            return substr($content, 0, $offset)
                . PHP_EOL
                . $insertBlock
                . substr($content, $offset);
        }

        return $content;
    }

    /**
     * @param array<int, array{interface: string, repository: string}> $bindings
     */
    private function bindingLines(array $bindings, bool $includeMarkers = true): string
    {
        $lines = [];

        if ($includeMarkers) {
            $lines[] = self::START_MARKER;
        }

        foreach ($bindings as $binding) {
            $lines[] = '        $this->app->bind(';
            $lines[] = '            ' . $binding['interface'] . ',';
            $lines[] = '            ' . $binding['repository'];
            $lines[] = '        );';
            $lines[] = '';
        }

        if ($includeMarkers) {
            if (end($lines) === '') {
                array_pop($lines);
            }

            $lines[] = self::END_MARKER;
        }

        return implode(PHP_EOL, $lines);
    }

    private function showRegistrationHint(): void
    {
        $this->newLine();
        $this->line('Add to bootstrap/providers.php if not already registered:');
        $this->line('');
        $this->line('    App\\Providers\\CrudForgeGeneratedServiceProvider::class,');
        $this->newLine();
    }
}

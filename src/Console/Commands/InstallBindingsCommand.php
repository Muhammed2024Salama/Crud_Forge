<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class InstallBindingsCommand extends Command
{
    protected $signature = 'crudforge:install-bindings
                            {name? : The generated model name, for example Product}
                            {--all : Detect all *RepositoryInterface files and install matching bindings}
                            {--force : Write changes without confirmation}';

    protected $description = 'Create or update App\Providers\CrudForgeGeneratedServiceProvider with generated repository bindings.';

    private const PROVIDER_PATH = 'app/Providers/CrudForgeGeneratedServiceProvider.php';

    private const START_MARKER = '        // <crudforge-bindings>';

    private const END_MARKER = '        // </crudforge-bindings>';

    public function handle(Filesystem $files): int
    {
        $bindings = $this->option('all')
            ? $this->detectBindings($files)
            : $this->bindingsForName((string) $this->argument('name'));

        if ($bindings === []) {
            $this->error('No bindings found. Pass a model name or use --all after generating repositories.');

            return self::FAILURE;
        }

        $providerPath = base_path(self::PROVIDER_PATH);
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

            $this->info('Created app/Providers/CrudForgeGeneratedServiceProvider.php');
            $this->showProviderRegistrationInstructions();

            return self::SUCCESS;
        }

        $existingContent = $files->get($providerPath);
        $updatedContent = $this->appendMissingBindings($existingContent, $bindings);

        if ($updatedContent === $existingContent) {
            $this->info('No missing bindings found. Provider is already up to date.');

            return self::SUCCESS;
        }

        $files->put($providerPath, $updatedContent);

        $this->info('Updated app/Providers/CrudForgeGeneratedServiceProvider.php');
        $this->showProviderRegistrationInstructions();

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{interface: string, repository: string}>
     */
    private function bindingsForName(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return [];
        }

        $studlyName = Str::studly($name);

        return [[
            'interface' => "\\App\\Interfaces\\{$studlyName}RepositoryInterface::class",
            'repository' => "\\App\\Repositories\\{$studlyName}Repository::class",
        ]];
    }

    /**
     * @return array<int, array{interface: string, repository: string}>
     */
    private function detectBindings(Filesystem $files): array
    {
        $interfacePath = app_path('Interfaces');
        $repositoryPath = app_path('Repositories');

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
            $repositoryFile = $repositoryPath . DIRECTORY_SEPARATOR . $baseName . 'Repository.php';

            if (! $files->exists($repositoryFile)) {
                continue;
            }

            $bindings[] = [
                'interface' => "\\App\\Interfaces\\{$baseName}RepositoryInterface::class",
                'repository' => "\\App\\Repositories\\{$baseName}Repository::class",
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
        $missingBindings = array_values(array_filter(
            $bindings,
            static fn (array $binding): bool => ! str_contains($content, $binding['interface'])
        ));

        if ($missingBindings === []) {
            return $content;
        }

        $newLines = $this->bindingLines($missingBindings, includeMarkers: false);

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

    private function showProviderRegistrationInstructions(): void
    {
        $this->line('');
        $this->line('Register the provider in bootstrap/providers.php if it is not already registered:');
        $this->line('');
        $this->line('    App\\Providers\\CrudForgeGeneratedServiceProvider::class,');
        $this->line('');
    }
}

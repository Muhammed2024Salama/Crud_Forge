<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Console\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'crudforge:install';

    protected $description = 'Publish CrudForge configuration and confirm the package is ready.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'crudforge-config']);

        $this->newLine();
        $this->components->info('CrudForge installed successfully.');
        $this->newLine();

        $this->components->twoColumnDetail('Auto-discovery',    '<fg=green>✓</> Both service providers registered');
        $this->components->twoColumnDetail('Route loading',     '<fg=green>✓</> routes/crudforge.php (single manifest, no glob)');
        $this->components->twoColumnDetail('Binding registry',  '<fg=green>✓</> bootstrap/crudforge-bindings.php');
        $this->components->twoColumnDetail('bootstrap/providers.php', '<fg=green>✓</> Never modified');

        $this->newLine();
        $this->line('  Generate your first module:');
        $this->newLine();
        $this->line('  <fg=yellow>php artisan crudforge:generate Order --fields="name:string,total:decimal,status:boolean"</>');
        $this->newLine();

        return self::SUCCESS;
    }
}

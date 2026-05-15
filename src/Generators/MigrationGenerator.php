<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class MigrationGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [[
            'path'    => $this->resolveMigrationPath($context['table']),
            'content' => $this->render('migration', $context),
        ]];
    }

    /**
     * Re-use an existing migration file for the same table so that regenerating
     * a module does not create multiple conflicting migration files.
     */
    private function resolveMigrationPath(string $table): string
    {
        $pattern = database_path("migrations/*_create_{$table}_table.php");
        $matches = glob($pattern);

        if ($matches !== false && $matches !== []) {
            return $matches[0];
        }

        return database_path('migrations/' . date('Y_m_d_His') . "_create_{$table}_table.php");
    }
}

<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class MigrationGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [
            'path' => database_path("migrations/" . date('Y_m_d_His') . "_create_{$context['table']}_table.php"),
            'content' => $this->render('migration', $context),
        ];
    }
}

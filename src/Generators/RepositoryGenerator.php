<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class RepositoryGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('repositories', app_path('Repositories'));

        return [[
            'path'    => $base . DIRECTORY_SEPARATOR . "{$context['model']}Repository.php",
            'content' => $this->render('repository', $context),
        ]];
    }
}

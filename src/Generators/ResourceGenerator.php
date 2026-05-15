<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class ResourceGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('resources', app_path('Http/Resources'));

        return [[
            'path'    => $base . DIRECTORY_SEPARATOR . "{$context['model']}Resource.php",
            'content' => $this->render('resource', $context),
        ]];
    }
}

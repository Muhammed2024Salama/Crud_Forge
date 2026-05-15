<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class InterfaceGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('interfaces', app_path('Interfaces'));

        return [[
            'path'    => $base . DIRECTORY_SEPARATOR . "{$context['model']}RepositoryInterface.php",
            'content' => $this->render('interface', $context),
        ]];
    }
}

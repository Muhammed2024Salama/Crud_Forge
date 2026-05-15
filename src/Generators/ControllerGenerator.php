<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class ControllerGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('controllers', app_path('Http/Controllers/Api'));

        return [[
            'path'    => $base . DIRECTORY_SEPARATOR . "{$context['model']}Controller.php",
            'content' => $this->render('controller', $context),
        ]];
    }
}

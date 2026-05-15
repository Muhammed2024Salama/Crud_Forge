<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class ModelGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('models', app_path('Models'));

        return [[
            'path'    => $base . DIRECTORY_SEPARATOR . "{$context['model']}.php",
            'content' => $this->render('model', $context),
        ]];
    }
}

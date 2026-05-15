<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class ServiceGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);
        $base    = $this->outputPath('services', app_path('Services'));

        return [[
            'path'    => $base . DIRECTORY_SEPARATOR . "{$context['model']}Service.php",
            'content' => $this->render('service', $context),
        ]];
    }
}

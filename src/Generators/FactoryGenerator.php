<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class FactoryGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [[
            'path'    => database_path("factories/{$context['model']}Factory.php"),
            'content' => $this->render('factory', $context),
        ]];
    }
}

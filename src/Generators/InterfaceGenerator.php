<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class InterfaceGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [
            'path' => app_path("Interfaces/{$context['model']}RepositoryInterface.php"),
            'content' => $this->render('interface', $context),
        ];
    }
}

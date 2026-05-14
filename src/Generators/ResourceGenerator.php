<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class ResourceGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [
            'path' => app_path("Http/Resources/{$context['model']}Resource.php"),
            'content' => $this->render('resource', $context),
        ];
    }
}

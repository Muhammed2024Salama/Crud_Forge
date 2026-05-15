<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class RouteSnippetGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [[
            'path'    => base_path("routes/crudforge-{$context['route']}.php"),
            'content' => $this->render('route', $context),
        ]];
    }
}

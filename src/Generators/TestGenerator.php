<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class TestGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [
            'path' => base_path("tests/Feature/{$context['model']}ApiTest.php"),
            'content' => $this->render('test', $context),
        ];
    }
}

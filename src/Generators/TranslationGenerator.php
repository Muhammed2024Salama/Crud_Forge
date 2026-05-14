<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class TranslationGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context = $this->buildContext($name, $fields);

        return [
            [
                'path' => lang_path("en/{$context['translation']}.php"),
                'content' => $this->render('lang_en', $context),
            ],
            [
                'path' => lang_path("ar/{$context['translation']}.php"),
                'content' => $this->render('lang_ar', $context),
            ],
        ];
    }
}

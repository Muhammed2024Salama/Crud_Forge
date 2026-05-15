<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Generators;

final class TranslationGenerator extends AbstractGenerator
{
    /** @param array<int, array<string, mixed>> $fields */
    public function generate(string $name, array $fields): array
    {
        $context  = $this->buildContext($name, $fields);
        $langBase = $this->outputPath('lang', lang_path());

        return [
            [
                'path'    => $langBase . DIRECTORY_SEPARATOR . "en" . DIRECTORY_SEPARATOR . "{$context['translation']}.php",
                'content' => $this->render('lang_en', $context),
            ],
            [
                'path'    => $langBase . DIRECTORY_SEPARATOR . "ar" . DIRECTORY_SEPARATOR . "{$context['translation']}.php",
                'content' => $this->render('lang_ar', $context),
            ],
        ];
    }
}

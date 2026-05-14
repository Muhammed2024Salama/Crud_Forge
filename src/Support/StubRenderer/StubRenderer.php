<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Support\StubRenderer;

use RuntimeException;

final class StubRenderer
{
    /**
     * @param array<string, string|int|float|bool|null> $replacements
     */
    public function render(string $stubPath, array $replacements): string
    {
        if (! is_file($stubPath)) {
            throw new RuntimeException("Stub file not found: {$stubPath}");
        }

        $content = file_get_contents($stubPath);

        if ($content === false) {
            throw new RuntimeException("Unable to read stub file: {$stubPath}");
        }

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{ ' . $key . ' }}', (string) $value, $content);
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }
}

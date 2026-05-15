<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Support\StubRenderer;

use MuhammedSalama\CrudForge\Contracts\StubRendererContract;
use RuntimeException;

final class StubRenderer implements StubRendererContract
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

        if (preg_match('/\{\{\s*\w+\s*\}\}/', $content, $leftover)) {
            throw new RuntimeException(
                "Stub rendered with unreplaced placeholder: {$leftover[0]} in {$stubPath}"
            );
        }

        return $content;
    }
}

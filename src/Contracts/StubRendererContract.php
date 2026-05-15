<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Contracts;

interface StubRendererContract
{
    /**
     * Render a stub file, replacing all {{ key }} placeholders with the given values.
     *
     * @param array<string, string|int|float|bool|null> $replacements
     */
    public function render(string $stubPath, array $replacements): string;
}

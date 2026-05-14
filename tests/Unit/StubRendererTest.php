<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Support\StubRenderer\StubRenderer;
use MuhammedSalama\CrudForge\Tests\TestCase;

final class StubRendererTest extends TestCase
{
    public function test_it_replaces_placeholders(): void
    {
        $path = sys_get_temp_dir() . '/crudforge_test.stub';
        file_put_contents($path, 'Hello {{ name }}');

        $content = (new StubRenderer())->render($path, ['name' => 'CrudForge']);

        $this->assertSame('Hello CrudForge', $content);

        @unlink($path);
    }
}

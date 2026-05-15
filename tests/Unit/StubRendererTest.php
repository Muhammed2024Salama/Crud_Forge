<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Contracts\StubRendererContract;
use MuhammedSalama\CrudForge\Support\StubRenderer\StubRenderer;
use MuhammedSalama\CrudForge\Tests\TestCase;
use RuntimeException;

final class StubRendererTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = sys_get_temp_dir() . '/crudforge_test_' . uniqid() . '.stub';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }

        parent::tearDown();
    }

    public function test_it_implements_contract(): void
    {
        $this->assertInstanceOf(StubRendererContract::class, new StubRenderer());
    }

    public function test_it_replaces_spaced_placeholders(): void
    {
        file_put_contents($this->tmpFile, 'Hello {{ name }}');

        $content = (new StubRenderer())->render($this->tmpFile, ['name' => 'CrudForge']);

        $this->assertSame('Hello CrudForge', $content);
    }

    public function test_it_replaces_compact_placeholders(): void
    {
        file_put_contents($this->tmpFile, 'Hello {{name}}');

        $content = (new StubRenderer())->render($this->tmpFile, ['name' => 'World']);

        $this->assertSame('Hello World', $content);
    }

    public function test_it_replaces_multiple_occurrences(): void
    {
        file_put_contents($this->tmpFile, '{{ model }} and {{ model }}');

        $content = (new StubRenderer())->render($this->tmpFile, ['model' => 'Product']);

        $this->assertSame('Product and Product', $content);
    }

    public function test_it_handles_empty_string_replacement(): void
    {
        file_put_contents($this->tmpFile, 'prefix{{ castsBlock }}suffix');

        $content = (new StubRenderer())->render($this->tmpFile, ['castsBlock' => '']);

        $this->assertSame('prefixsuffix', $content);
    }

    public function test_it_throws_for_missing_stub_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stub file not found/');

        (new StubRenderer())->render('/nonexistent/stub.stub', []);
    }

    public function test_it_is_bound_as_contract_in_container(): void
    {
        $renderer = $this->app->make(StubRendererContract::class);

        $this->assertInstanceOf(StubRendererContract::class, $renderer);
        $this->assertInstanceOf(StubRenderer::class, $renderer);
    }

    public function test_it_is_a_singleton(): void
    {
        $a = $this->app->make(StubRendererContract::class);
        $b = $this->app->make(StubRendererContract::class);

        $this->assertSame($a, $b);
    }
}

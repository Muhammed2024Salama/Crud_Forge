<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Support\Fields\FieldParser;
use MuhammedSalama\CrudForge\Tests\TestCase;
use InvalidArgumentException;

final class FieldParserTest extends TestCase
{
    public function test_it_parses_fields(): void
    {
        $fields = (new FieldParser())->parse('name:string,price:decimal,status:boolean');

        $this->assertCount(3, $fields);
        $this->assertSame('name', $fields[0]['name']);
        $this->assertSame('decimal', $fields[1]['type']);
        $this->assertTrue($fields[0]['searchable']);
    }

    public function test_it_rejects_unsafe_field_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FieldParser())->parse('name;drop:string');
    }
}

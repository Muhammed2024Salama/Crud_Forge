<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Support\Fields\FieldParser;
use MuhammedSalama\CrudForge\Tests\TestCase;
use InvalidArgumentException;

final class FieldParserTest extends TestCase
{
    private FieldParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FieldParser();
    }

    public function test_it_parses_basic_fields(): void
    {
        $fields = $this->parser->parse('name:string,price:decimal,status:boolean');

        $this->assertCount(3, $fields);
        $this->assertSame('name', $fields[0]['name']);
        $this->assertSame('string', $fields[0]['type']);
        $this->assertSame('decimal', $fields[1]['type']);
        $this->assertSame('boolean', $fields[2]['type']);
    }

    public function test_searchable_flags_are_correct(): void
    {
        $fields = $this->parser->parse('title:string,body:text,count:integer');

        $this->assertTrue($fields[0]['searchable']);
        $this->assertTrue($fields[1]['searchable']);
        $this->assertFalse($fields[2]['searchable']);
    }

    public function test_sortable_flags_are_correct(): void
    {
        $fields = $this->parser->parse('name:string,price:decimal,meta:json');

        $this->assertTrue($fields[0]['sortable']);
        $this->assertTrue($fields[1]['sortable']);
        $this->assertFalse($fields[2]['sortable']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('typeAliasProvider')]
    public function test_type_aliases_normalize_correctly(string $input, string $expected): void
    {
        $fields = $this->parser->parse("value:{$input}");

        $this->assertSame($expected, $fields[0]['type']);
    }

    /** @return array<string, array{string, string}> */
    public static function typeAliasProvider(): array
    {
        return [
            'str'            => ['str', 'string'],
            'varchar'        => ['varchar', 'string'],
            'int'            => ['int', 'integer'],
            'bigint'         => ['bigint', 'bigInteger'],
            'biginteger'     => ['biginteger', 'bigInteger'],
            'uint'           => ['uint', 'unsignedInteger'],
            'bool'           => ['bool', 'boolean'],
            'float'          => ['float', 'decimal'],
            'double'         => ['double', 'decimal'],
            'foreignid'      => ['foreignId', 'foreignId'],
            'foreign_id'     => ['foreign_id', 'foreignId'],
            'json'           => ['json', 'json'],
            'jsonb'          => ['jsonb', 'json'],
            'datetime'       => ['datetime', 'datetime'],
            'timestamp'           => ['timestamp', 'timestamp'],
            'ubigint'             => ['ubigint', 'unsignedBigInteger'],
            'unsignedBigInteger'  => ['unsignedBigInteger', 'unsignedBigInteger'],
        ];
    }

    public function test_store_rules_use_required(): void
    {
        $fields = $this->parser->parse('name:string');

        $this->assertStringContainsString("'required'", $fields[0]['validation']);
    }

    public function test_update_rules_use_sometimes_instead_of_required(): void
    {
        $fields = $this->parser->parse('name:string,count:integer');

        $this->assertStringContainsString("'sometimes'", $fields[0]['updateValidation']);
        $this->assertStringNotContainsString("'required'", $fields[0]['updateValidation']);
        $this->assertStringContainsString("'sometimes'", $fields[1]['updateValidation']);
    }

    public function test_nullable_fields_have_same_validation_for_store_and_update(): void
    {
        $fields = $this->parser->parse('notes:text');

        $this->assertStringContainsString("'nullable'", $fields[0]['validation']);
        $this->assertSame($fields[0]['validation'], $fields[0]['updateValidation']);
    }

    public function test_cast_entry_populated_for_castable_types(): void
    {
        $fields = $this->parser->parse('active:boolean,published_at:date,meta:json,price:decimal');

        $this->assertStringContainsString("'boolean'", $fields[0]['castEntry']);
        $this->assertStringContainsString("'date'", $fields[1]['castEntry']);
        $this->assertStringContainsString("'array'", $fields[2]['castEntry']);
        $this->assertStringContainsString("'decimal:2'", $fields[3]['castEntry']);
    }

    public function test_cast_entry_empty_for_non_castable_types(): void
    {
        $fields = $this->parser->parse('name:string,count:integer');

        $this->assertSame('', $fields[0]['castEntry']);
        $this->assertSame('', $fields[1]['castEntry']);
    }

    public function test_migration_line_generated_for_each_type(): void
    {
        $fields = $this->parser->parse(
            'name:string,body:text,count:integer,big:bigInteger,active:boolean,' .
            'price:decimal,born:date,created:datetime,stamp:timestamp,meta:json,user_id:foreignId'
        );

        $migrationValues = array_column($fields, 'migration');

        $this->assertStringContainsString('string(', $migrationValues[0]);
        $this->assertStringContainsString('text(', $migrationValues[1]);
        $this->assertStringContainsString('integer(', $migrationValues[2]);
        $this->assertStringContainsString('bigInteger(', $migrationValues[3]);
        $this->assertStringContainsString('boolean(', $migrationValues[4]);
        $this->assertStringContainsString('decimal(', $migrationValues[5]);
        $this->assertStringContainsString('date(', $migrationValues[6]);
        $this->assertStringContainsString('dateTime(', $migrationValues[7]);
        $this->assertStringContainsString('timestamp(', $migrationValues[8]);
        $this->assertStringContainsString('json(', $migrationValues[9]);
        $this->assertStringContainsString('foreignId(', $migrationValues[10]);
        $this->assertStringContainsString('->constrained()', $migrationValues[10]);
    }

    public function test_it_rejects_null_or_empty_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse(null);
    }

    public function test_it_rejects_whitespace_only_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse('   ');
    }

    public function test_it_rejects_unsafe_field_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse('name;drop:string');
    }

    public function test_it_rejects_field_names_starting_with_digit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse('1name:string');
    }

    public function test_it_rejects_unsupported_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse('name:blob');
    }

    public function test_it_rejects_missing_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->parse('name:');
    }

    public function test_factory_line_generated_correctly(): void
    {
        $fields = $this->parser->parse('name:string,user_id:foreignId');

        $this->assertStringContainsString('fake()->words', $fields[0]['factory']);
        $this->assertStringContainsString("'user_id' => 1", $fields[1]['factory']);
    }
}

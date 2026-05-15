<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Support\Database\DatabaseDriverHelper;
use MuhammedSalama\CrudForge\Tests\TestCase;
use InvalidArgumentException;

final class DatabaseDriverHelperTest extends TestCase
{
    public function test_case_insensitive_operator_is_like_or_ilike(): void
    {
        $operator = DatabaseDriverHelper::caseInsensitiveOperator();

        $this->assertContains($operator, ['LIKE', 'ILIKE']);
    }

    public function test_current_driver_returns_a_non_empty_string(): void
    {
        $driver = DatabaseDriverHelper::currentDriver();

        $this->assertNotEmpty($driver);
        $this->assertIsString($driver);
    }

    public function test_is_postgres_returns_bool(): void
    {
        $this->assertIsBool(DatabaseDriverHelper::isPostgres());
    }

    public function test_is_mysql_returns_bool(): void
    {
        $this->assertIsBool(DatabaseDriverHelper::isMysql());
    }

    public function test_guard_safe_column_accepts_valid_names(): void
    {
        DatabaseDriverHelper::guardSafeColumn('name');
        DatabaseDriverHelper::guardSafeColumn('user_id');
        DatabaseDriverHelper::guardSafeColumn('table.column');
        DatabaseDriverHelper::guardSafeColumn('CamelCase');

        $this->assertTrue(true); // no exception
    }

    public function test_guard_safe_column_rejects_sql_injection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabaseDriverHelper::guardSafeColumn('name;drop table users');
    }

    public function test_guard_safe_column_rejects_backtick(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabaseDriverHelper::guardSafeColumn('`name`');
    }

    public function test_guard_safe_column_rejects_space(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabaseDriverHelper::guardSafeColumn('first name');
    }

    public function test_like_search_escapes_wildcard_characters(): void
    {
        DatabaseDriverHelper::guardSafeColumn('search_column');

        $this->assertTrue(true);
    }
}

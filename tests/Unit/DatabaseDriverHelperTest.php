<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Tests\Unit;

use MuhammedSalama\CrudForge\Support\Database\DatabaseDriverHelper;
use MuhammedSalama\CrudForge\Tests\TestCase;
use InvalidArgumentException;

final class DatabaseDriverHelperTest extends TestCase
{
    public function test_it_returns_a_case_insensitive_operator(): void
    {
        $this->assertContains(DatabaseDriverHelper::caseInsensitiveOperator(), ['LIKE', 'ILIKE']);
    }

    public function test_it_rejects_unsafe_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabaseDriverHelper::guardSafeColumn('name;drop table users');
    }
}

<?php

declare(strict_types=1);

namespace MuhammedSalama\CrudForge\Support\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DatabaseDriverHelper
{
    public static function currentDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function isPostgres(): bool
    {
        return self::currentDriver() === 'pgsql';
    }

    public static function isMysql(): bool
    {
        return self::currentDriver() === 'mysql';
    }

    public static function caseInsensitiveOperator(): string
    {
        return self::isPostgres() ? 'ILIKE' : 'LIKE';
    }

    /**
     * @template T of EloquentBuilder|QueryBuilder
     * @param T $query
     * @return T
     */
    public static function applyCaseInsensitiveSearch(EloquentBuilder|QueryBuilder $query, string $column, string $search): EloquentBuilder|QueryBuilder
    {
        self::guardSafeColumn($column);

        return $query->where($column, self::caseInsensitiveOperator(), '%' . self::escapeLike($search) . '%');
    }

    public static function guardSafeColumn(string $column): void
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $column)) {
            throw new InvalidArgumentException("Unsafe database column name: {$column}");
        }
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}

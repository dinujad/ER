<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Remembers Schema::hasTable / hasColumn answers for the rest of the request.
 * On MySQL each check is an information_schema query; drill-down pages used to run hundreds of them,
 * which made requests slow and kept database connections busy longer.
 */
class SchemaCache
{
    /** @var array<string, bool> */
    private static array $cache = [];

    public static function hasTable(string $table): bool
    {
        return self::$cache['t:' . $table] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::$cache['c:' . $table . '.' . $column] ??= Schema::hasColumn($table, $column);
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}

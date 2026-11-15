<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseTableDataReset
{
    /** @var list<string> */
    private const SKIP_TABLES = ['migrations'];

    /**
     * @return list<string> truncated table names
     */
    public function truncateAll(): array
    {
        $truncated = [];

        Schema::disableForeignKeyConstraints();

        foreach ($this->tableNames() as $table) {
            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }

            DB::table($table)->truncate();
            $truncated[] = $table;
        }

        Schema::enableForeignKeyConstraints();

        return $truncated;
    }

    /**
     * @return list<string>
     */
    private function tableNames(): array
    {
        $driver = Schema::getConnection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->all(),
            'pgsql' => collect(DB::select(
                "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
            ))->pluck('tablename')->all(),
            'sqlite' => collect(DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            ))
                ->pluck('name')
                ->reject(fn (string $name) => $name === 'sqlite_sequence')
                ->values()
                ->all(),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };
    }
}

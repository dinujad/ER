<?php

/**
 * Browser deploy utilities (no SSH). Registered at /deploy and /erp/deploy.
 * Set DEPLOY_KEY in .env — use that value in the URL, not a random number.
 */

use App\Models\ProductionSection;
use App\Models\Role;
use App\Services\AccountingService;
use App\Services\DatabaseTableDataReset;
use App\Services\FixFilesystemPermissions;
use App\Services\OperationsDataReset;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ProductionSectionSeeder;
use Database\Seeders\ResetDatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

return function (): void {
    $guard = function (string $key): void {
        $expected = trim((string) env('DEPLOY_KEY', 'dermas-2026'));
        if ($key === '' || !hash_equals($expected, $key)) {
            abort(403, 'Invalid deploy key. Check DEPLOY_KEY in your .env file.');
        }
    };

    $pre = function (string $key) use ($guard): string {
        $guard($key);

        return '<pre>';
    };

    $end = '</pre>';

    Route::get('/{key}', function (string $key) use ($guard) {
        $guard($key);
        $base = url('deploy/' . $key);
        $baseErp = url('erp/deploy/' . $key);

        $lines = [
            'DERMAS deploy utilities',
            'Key: ' . $key . ' (from DEPLOY_KEY in .env)',
            '',
            'Use either base URL:',
            '  ' . $base,
            '  ' . $baseErp,
            '',
            'Endpoints (append to base):',
            '  /db-test              — test MySQL',
            '  /migrate              — php artisan migrate --force',
            '  /migrate-fresh-status — migration status',
            '  /seed-admin           — create/update default admin user',
            '  /seed-permissions     — seed roles + give Admin all permission keys',
            '  /seed-defaults        — reference data (keeps existing rows)',
            '  /seed-sections        — production sections only',
            '  /config-clear         — clear config cache (run after .env change)',
            '  /route-clear          — clear route cache',
            '  /cache-clear          — clear app cache',
            '  /view-clear           — clear view cache',
            '  /fix-permissions      — storage/bootstrap permissions',
            '  /all                  — clear caches + migrate',
            '  /reset-operations     — DANGER: clear purchases, inventory stock, expenses + related journals',
            '  /accounting-repair    — collapse duplicate journals + backfill missing postings',
            '  /accounting-schema    — ensure Phase 2–5 accounting tables exist (safe / additive)',
            '  /reset-db             — DANGER: truncate data, re-seed (keeps admins)',
            '',
            'Example:',
            '  ' . $base . '/migrate',
            '  ' . $baseErp . '/db-test',
        ];

        return '<pre>' . implode("\n", $lines) . '</pre>';
    });

    Route::get('/{key}/db-test', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        try {
            DB::connection()->getPdo();
            $db = DB::connection()->getDatabaseName();
            $conn = config('database.default');
            $host = config("database.connections.{$conn}.host");

            return $pre($key) . "OK — connected to MySQL.\nDatabase: {$db}\nHost: {$host}\nDriver: {$conn}{$end}";
        } catch (Throwable $e) {
            $conn = config('database.default');
            $host = config("database.connections.{$conn}.host");
            $db = config("database.connections.{$conn}.database");

            return response(
                $pre($key) . "Database connection failed.\nHost: {$host}\nDatabase: {$db}\n\n{$e->getMessage()}\n\nconfig/database.php maps 127.0.0.1 → localhost.\nThen run: .../config-clear{$end}",
                500
            )->header('Content-Type', 'text/html; charset=UTF-8');
        }
    });

    Route::get('/{key}/migrate', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        try {
            Artisan::call('migrate', ['--force' => true]);
            return $pre($key) . Artisan::output() . $end;
        } catch (Throwable $e) {
            // Make hosting/debug easier: show exception class + message without requiring cPanel logs.
            $msg = $pre($key) . "MIGRATE FAILED\n"
                . "Exception: " . get_class($e) . "\n"
                . "Message: " . $e->getMessage() . "\n"
                . "Location: " . $e->getFile() . ":" . $e->getLine()
                . $end;

            \Illuminate\Support\Facades\Log::error('deploy migrate failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response($msg, 500)->header('Content-Type', 'text/plain; charset=utf-8');
        }
    });

    Route::get('/{key}/migrate-fresh-status', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        try {
            Artisan::call('migrate:status');
            return $pre($key) . Artisan::output() . $end;
        } catch (Throwable $e) {
            $msg = $pre($key) . "MIGRATE STATUS FAILED\n"
                . "Exception: " . get_class($e) . "\n"
                . "Message: " . $e->getMessage() . "\n"
                . "Location: " . $e->getFile() . ":" . $e->getLine()
                . $end;

            \Illuminate\Support\Facades\Log::error('deploy migrate:status failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response($msg, 500)->header('Content-Type', 'text/plain; charset=utf-8');
        }
    });

    Route::get('/{key}/seed-admin', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);

        return $pre($key) . "Admin user seeded/updated.\n" . Artisan::output() . "\nEmail: admin@dermasapparel.com{$end}";
    });

    Route::get('/{key}/seed-permissions', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        try {
            Artisan::call('db:seed', ['--class' => \Database\Seeders\RoleSeeder::class, '--force' => true]);
            $roles = Role::query()->orderBy('label')->get(['name', 'label', 'permissions']);
            $lines = [
                'Roles & permissions seeded.',
                'Admin role now has all keys from config/permissions.php.',
                'Other roles were not overwritten.',
                '',
                Artisan::output(),
                'Roles:',
            ];
            foreach ($roles as $role) {
                $count = is_array($role->permissions) ? count($role->permissions) : 0;
                $lines[] = '- ' . $role->label . ' (' . $role->name . ') — ' . $count . ' permission(s)';
            }

            return $pre($key) . implode("\n", $lines) . $end;
        } catch (Throwable $e) {
            $msg = $pre($key) . "SEED PERMISSIONS FAILED\n"
                . "Exception: " . get_class($e) . "\n"
                . "Message: " . $e->getMessage() . "\n"
                . "Location: " . $e->getFile() . ':' . $e->getLine()
                . $end;

            return response($msg, 500)->header('Content-Type', 'text/plain; charset=utf-8');
        }
    });

    Route::get('/{key}/seed-defaults', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('db:seed', ['--class' => ResetDatabaseSeeder::class, '--force' => true]);
        $sections = ProductionSection::whereNull('parent_id')->count();

        return $pre($key) . "Seed complete (existing data kept).\nProduction sections (root): {$sections}\n\n" . Artisan::output() . $end;
    });

    Route::get('/{key}/seed-sections', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('db:seed', ['--class' => ProductionSectionSeeder::class, '--force' => true]);
        $names = ProductionSection::whereNull('parent_id')->pluck('name')->implode(', ');

        return $pre($key) . "Production sections seeded.\nRoot: " . ($names ?: '(none)') . "\n\n" . Artisan::output() . $end;
    });

    Route::get('/{key}/accounting-schema', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        $files = [
            '2026_11_15_140000_create_accounting_phase2_tables.php',
            '2026_11_15_160000_phase2_completion_bank_match_and_recurring_docs.php',
            '2026_11_15_200000_create_accounting_phase3_tables.php',
            '2026_11_15_210000_phase3_completion_tables.php',
            '2026_11_15_220000_create_accounting_phase4_tables.php',
            '2026_11_15_230000_create_accounting_phase5_tables.php',
            '2026_11_15_240000_add_optional_journal_entry_columns.php',
        ];

        $lines = ['Accounting schema ensure (idempotent migration up()).', ''];
        $ok = 0;
        $fail = 0;

        try {
            foreach ($files as $file) {
                $path = database_path('migrations/'.$file);
                if (! is_file($path)) {
                    $lines[] = 'MISSING FILE: '.$file;
                    $fail++;
                    continue;
                }

                try {
                    $migration = require $path;
                    if (is_object($migration) && method_exists($migration, 'up')) {
                        $migration->up();
                        $lines[] = 'OK: '.$file;
                        $ok++;
                    } else {
                        $lines[] = 'SKIP (no up): '.$file;
                    }
                } catch (Throwable $e) {
                    $lines[] = 'FAIL: '.$file.' — '.$e->getMessage();
                    $fail++;
                }
            }

            $checks = [
                'accounting_recurring_templates',
                'accounting_recurring_runs',
                'accounting_periods',
                'accounting_manual_journals',
                'fa_assets',
                'acct_cost_centers',
                'acct_ai_conversations',
            ];
            $lines[] = '';
            $lines[] = 'Table checks:';
            foreach ($checks as $table) {
                $lines[] = '- '.$table.': '.(Schema::hasTable($table) ? 'exists' : 'MISSING');
            }

            $lines[] = '';
            $lines[] = "Done. OK={$ok} FAIL={$fail}";

            return $pre($key).implode("\n", $lines).$end;
        } catch (Throwable $e) {
            return $pre($key)."ACCOUNTING SCHEMA FAILED\n"
                .'Exception: '.get_class($e)."\n"
                .'Message: '.$e->getMessage()."\n"
                .'Location: '.$e->getFile().':'.$e->getLine()
                .$end;
        }
    });

    Route::get('/{key}/accounting-repair', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ChartOfAccountsSeeder', '--force' => true]);
            $coaSeed = trim(Artisan::output());
            $collapsed = AccountingService::collapseDuplicateJournals();
            Artisan::call('accounting:backfill', ['--force' => true]);
            $lines = [
                'Accounting repair complete.',
                'Chart of accounts seeded (safe / existing kept).',
                $coaSeed !== '' ? $coaSeed : '(no seeder output)',
                'Duplicate journal groups reversed: ' . $collapsed['groups'],
                'Extra transaction numbers reversed: ' . $collapsed['reversed'],
                '',
                'Backfill (sales / purchases / expenses / payments / payroll / inventory):',
                trim(Artisan::output()),
            ];

            return $pre($key) . implode("\n", $lines) . $end;
        } catch (Throwable $e) {
            return $pre($key) . "ACCOUNTING REPAIR FAILED\n"
                . 'Exception: ' . get_class($e) . "\n"
                . 'Message: ' . $e->getMessage() . "\n"
                . 'Location: ' . $e->getFile() . ':' . $e->getLine()
                . $end;
        }
    });

    Route::get('/{key}/reset-operations', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        try {
            $result = (new OperationsDataReset())->reset();
            $lines = [
                'Purchases + inventory + expenses reset complete.',
                'Products, orders, manufacturing, users, suppliers, expense categories were kept.',
                '',
                'Tables truncated: ' . implode(', ', $result['truncated'] ?: ['(none)']),
                'Tables missing (skipped): ' . implode(', ', $result['skipped'] ?: ['(none)']),
                'Material stock qty reset to 0: ' . $result['materials_reset'] . ' row(s)',
                'Journal entries removed: ' . $result['journals_removed'],
                'Accounting postings removed: ' . $result['postings_removed'],
            ];

            return $pre($key) . implode("\n", $lines) . $end;
        } catch (Throwable $e) {
            return $pre($key) . "RESET OPERATIONS FAILED\n"
                . 'Exception: ' . get_class($e) . "\n"
                . 'Message: ' . $e->getMessage() . "\n"
                . 'Location: ' . $e->getFile() . ':' . $e->getLine()
                . $end;
        }
    });

    Route::get('/{key}/reset-db', function (string $key) use ($guard, $pre, $end) {
        $guard($key);

        $admins = DB::table('users')->where('role', 'admin')->get()->map(fn ($row) => (array) $row)->all();
        $truncated = (new DatabaseTableDataReset())->truncateAll();

        Artisan::call('db:seed', ['--class' => ResetDatabaseSeeder::class, '--force' => true]);
        $seedOutput = Artisan::output();

        $adminRoleId = Role::query()->where('name', 'admin')->value('id');
        $restored = 0;
        foreach ($admins as $admin) {
            unset($admin['id']);
            if ($adminRoleId) {
                $admin['role_id'] = $adminRoleId;
            }
            DB::table('users')->insert($admin);
            $restored++;
        }

        $lines = [
            'Database data reset complete (no migrations run).',
            'Tables truncated: ' . count($truncated),
            "Admin users preserved: {$restored}",
        ];
        if ($restored === 0) {
            $lines[] = 'No admin before reset — run .../seed-admin';
        }

        return $pre($key) . implode("\n", $lines) . "\n\n" . $seedOutput . $end;
    });

    Route::get('/{key}/fix-permissions', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        $stats = (new FixFilesystemPermissions())->run();
        $lines = [
            'Permissions updated.',
            'Directories: ' . $stats['dirs'],
            'Files: ' . $stats['files'],
        ];
        if ($stats['errors'] !== []) {
            $lines[] = 'Errors (' . count($stats['errors']) . '):';
            $lines = array_merge($lines, array_slice($stats['errors'], 0, 20));
        }

        return $pre($key) . implode("\n", $lines) . $end;
    });

    Route::get('/{key}/view-clear', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('view:clear');

        return $pre($key) . Artisan::output() . "\nview cache cleared{$end}";
    });

    Route::get('/{key}/route-clear', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('route:clear');

        return $pre($key) . Artisan::output() . "\nroute cache cleared{$end}";
    });

    Route::get('/{key}/config-clear', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('config:clear');

        return $pre($key) . Artisan::output() . "\nconfig cache cleared{$end}";
    });

    Route::get('/{key}/cache-clear', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        Artisan::call('cache:clear');

        return $pre($key) . Artisan::output() . "\napplication cache cleared{$end}";
    });

    Route::get('/{key}/all', function (string $key) use ($guard, $pre, $end) {
        $guard($key);
        $output = [];

        foreach (['view:clear', 'route:clear', 'config:clear', 'cache:clear'] as $cmd) {
            Artisan::call($cmd);
            $output[] = "{$cmd}\n" . Artisan::output();
        }

        Artisan::call('migrate', ['--force' => true]);
        $output[] = "migrate --force\n" . Artisan::output();

        return $pre($key) . implode("\n----------------------------------------\n", $output) . $end;
    });
};

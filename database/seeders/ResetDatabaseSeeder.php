<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds reference data after a DB reset without touching admin users
 * (those are preserved and re-inserted by the deploy/reset-db route).
 */
class ResetDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProductionStageSeeder::class,
            ProductionSectionSeeder::class,
            ProductionLineSeeder::class,
            StoreSeeder::class,
            SupplierSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);
    }
}

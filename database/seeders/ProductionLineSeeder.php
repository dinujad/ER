<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if lines exist, create defaults if not
        if (DB::table('production_lines')->count() == 0) {
            DB::table('production_lines')->insert([
                [
                    'name' => 'Line 01',
                    'description' => 'Main Production Line',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Line 02',
                    'description' => 'Secondary Production Line',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Line 03',
                    'description' => 'Specialized Line',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}

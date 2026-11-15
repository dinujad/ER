<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionSection;

class ProductionSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cutting Section
        if (!ProductionSection::where('slug', 'cutting')->exists()) {
            ProductionSection::create([
                'name' => 'Cutting Section',
                'slug' => 'cutting',
                'type' => 'department',
            ]);
        }

        // 2. Washing Section
        if (!ProductionSection::where('slug', 'washing')->exists()) {
            ProductionSection::create([
                'name' => 'Washing Section',
                'slug' => 'washing',
                'type' => 'department',
            ]);
        }
        
        // 3. Finishing/Packing Section
        if (!ProductionSection::where('slug', 'finishing')->exists()) {
            ProductionSection::create([
                'name' => 'Finishing & Packing',
                'slug' => 'finishing',
                'type' => 'department',
            ]);
        }

        // 4. Sewing Department (Container for Lines)
        $sewing = ProductionSection::firstOrCreate(
            ['slug' => 'sewing'],
            [
                'name' => 'Sewing Lines',
                'type' => 'department',
                'description' => 'Manage Sewing Lines here'
            ]
        );

        // 4.1 Sub-Lines
        if ($sewing->children()->count() == 0) {
            $sewing->children()->createMany([
                [
                    'name' => 'Line 01',
                    'slug' => 'line-01',
                    'type' => 'line',
                ],
                [
                    'name' => 'Line 02',
                    'slug' => 'line-02',
                    'type' => 'line',
                ],
                [
                    'name' => 'Line 03',
                    'slug' => 'line-03',
                    'type' => 'line',
                ]
            ]);
        }
    }
}

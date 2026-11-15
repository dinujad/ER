<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Legacy + Direct
            ['name' => 'Fabric', 'code' => 'FABRIC', 'description' => 'Direct — main fabric & lining'],
            ['name' => 'Trims & Accessories', 'code' => 'TRIMS', 'description' => 'Direct — thread, buttons, zippers, labels, tags'],
            ['name' => 'Direct Labor / Sewing', 'code' => 'DIRECT_LABOR', 'description' => 'Direct — piece rate / sewing wages'],
            ['name' => 'Embellishments', 'code' => 'EMBELLISH', 'description' => 'Direct — embroidery, printing, washing/dyeing (general)'],
            ['name' => 'Embroidery', 'code' => 'EMBROIDERY', 'description' => 'Direct — outsourced embroidery'],
            ['name' => 'Printing', 'code' => 'PRINTING', 'description' => 'Direct — outsourced printing'],

            // Utilities
            ['name' => 'Utilities', 'code' => 'UTILITIES', 'description' => 'Utility bills (general)'],
            ['name' => 'Electricity', 'code' => 'ELECTRICITY', 'description' => 'Utility — electricity bill'],
            ['name' => 'Water', 'code' => 'WATER', 'description' => 'Utility — water bill'],
            ['name' => 'Communication', 'code' => 'COMMUNICATION', 'description' => 'Utility — internet / phone'],

            // Maintenance
            ['name' => 'Maintenance', 'code' => 'MAINT', 'description' => 'Factory & machine maintenance (general)'],
            ['name' => 'Machine Consumables', 'code' => 'MACHINE_CONSUMABLES', 'description' => 'Needles, oil, spare parts'],
            ['name' => 'Machine Repairs', 'code' => 'MACHINE_REPAIR', 'description' => 'Machine repair / cutter sharpening'],
            ['name' => 'Factory Maintenance', 'code' => 'FACTORY_MAINT', 'description' => 'Building minor repairs, bulbs, etc.'],

            // Packing & Logistics
            ['name' => 'Packing Materials', 'code' => 'PACKING', 'description' => 'Cartons, polybags, hangers, tape'],
            ['name' => 'Transport', 'code' => 'TRANSPORT', 'description' => 'Transport / logistics (general)'],
            ['name' => 'Inward Transport', 'code' => 'INWARD_TRANSPORT', 'description' => 'Bring fabric/materials to factory'],
            ['name' => 'Outward Transport', 'code' => 'OUTWARD_TRANSPORT', 'description' => 'Deliver finished goods / courier'],

            // Indirect labor
            ['name' => 'Salaries & Wages', 'code' => 'SALARIES', 'description' => 'Indirect labor & staff salaries'],
            ['name' => 'Indirect Labor', 'code' => 'INDIRECT_LABOR', 'description' => 'Supervisors, helpers, security, merchandisers'],

            // Admin / fixed
            ['name' => 'Office', 'code' => 'OFFICE', 'description' => 'Administration / office expenses'],
            ['name' => 'Rent', 'code' => 'RENT', 'description' => 'Building / land rent'],
            ['name' => 'Stationery & Printing', 'code' => 'STATIONERY', 'description' => 'Stationery, bill books, printer ink'],
            ['name' => 'Staff Welfare', 'code' => 'WELFARE', 'description' => 'Tea, meals, medical welfare'],
            ['name' => 'Depreciation', 'code' => 'DEPRECIATION', 'description' => 'Machine / vehicle depreciation'],
            ['name' => 'Miscellaneous', 'code' => 'MISC', 'description' => 'Other admin / fixed costs'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::updateOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}

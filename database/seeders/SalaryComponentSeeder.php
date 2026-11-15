<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['name' => 'Transport Allowance', 'code' => 'TA', 'type' => 'allowance', 'calculation_type' => 'fixed', 'default_value' => 5000, 'sort_order' => 1],
            ['name' => 'EPF (Employee)', 'code' => 'EPF', 'type' => 'deduction', 'calculation_type' => 'percentage', 'default_value' => 8, 'sort_order' => 10],
            ['name' => 'ETF', 'code' => 'ETF', 'type' => 'deduction', 'calculation_type' => 'percentage', 'default_value' => 3, 'sort_order' => 11],
        ];

        foreach ($components as $c) {
            SalaryComponent::firstOrCreate(['code' => $c['code']], $c);
        }
    }
}

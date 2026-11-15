<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        if (Supplier::exists()) {
            return;
        }
        Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Sample Supplier',
            'company_name' => 'Sample Fabrics Ltd',
            'phone' => '0112345678',
            'email' => 'supplier@example.com',
            'address' => '123 Main St, Colombo',
            'status' => 'active',
            'opening_balance' => 0,
        ]);
    }
}

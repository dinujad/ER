<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'label' => 'Admin'],
            ['name' => 'storekeeper', 'label' => 'Storekeeper'],
            ['name' => 'line_supervisor', 'label' => 'Line Supervisor'],
            ['name' => 'cashier', 'label' => 'Cashier'],
            ['name' => 'hr', 'label' => 'HR Manager'],
            ['name' => 'sales_agent', 'label' => 'Sales Agent'],
            ['name' => 'employee', 'label' => 'Employee'],
            
            // New Roles for Manufacturing
            ['name' => 'sewing_operator', 'label' => 'Sewing Operator'],
            ['name' => 'helper', 'label' => 'Helper'],
            ['name' => 'iron_operator', 'label' => 'Iron Operator'],
            ['name' => 'quality_checker', 'label' => 'Quality Checker'],
            ['name' => 'cutting_master', 'label' => 'Cutting Master'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['label' => $role['label']]
            );
        }

        $admin = Role::query()->where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions = Role::allPermissionKeys();
            $admin->save();
        }
    }
}

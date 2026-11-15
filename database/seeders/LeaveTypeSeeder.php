<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Annual Leave', 'code' => 'AL', 'days_per_year' => 14, 'is_paid' => true, 'requires_approval' => true],
            ['name' => 'Sick Leave', 'code' => 'SL', 'days_per_year' => 7, 'is_paid' => true, 'requires_approval' => true],
            ['name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 7, 'is_paid' => true, 'requires_approval' => true],
        ];

        foreach ($types as $t) {
            LeaveType::firstOrCreate(['code' => $t['code']], $t);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            [
                'name' => 'Kamal Perera',
                'email' => 'kamal@example.com',
                'nic' => '901234567V',
                'contact' => '0712345678',
                'role' => 'sewing_operator',
                'assigned_line' => 'Line 1',
                'join_date' => '2023-01-15',
            ],
            [
                'name' => 'Nimali Silva',
                'email' => 'nimali@example.com',
                'nic' => '925678123V',
                'contact' => '0779876543',
                'role' => 'sewing_operator',
                'assigned_line' => 'Line 1',
                'join_date' => '2023-02-10',
            ],
            [
                'name' => 'Sunil Fernando',
                'email' => 'sunil@example.com',
                'nic' => '882345678V',
                'contact' => '0751122334',
                'role' => 'helper',
                'assigned_line' => 'Line 1',
                'join_date' => '2023-03-01',
            ],
            [
                'name' => 'Chathuri Kumari',
                'email' => 'chathuri@example.com',
                'nic' => '957891234V',
                'contact' => '0704455667',
                'role' => 'line_supervisor',
                'assigned_line' => 'Line 1',
                'join_date' => '2022-11-20',
            ],
            [
                'name' => 'Amara Weerasinghe',
                'email' => 'amara@example.com',
                'nic' => '850012345V',
                'contact' => '0729988776',
                'role' => 'sewing_operator',
                'assigned_line' => 'Line 2',
                'join_date' => '2023-05-12',
            ],
            [
                'name' => 'Kasun Jayasuriya',
                'email' => 'kasun@example.com',
                'nic' => '931122334V',
                'contact' => '0783344556',
                'role' => 'sewing_operator',
                'assigned_line' => 'Line 2',
                'join_date' => '2023-06-05',
            ],
            [
                'name' => 'Ruwan Ekanayake',
                'email' => 'ruwan@example.com',
                'nic' => '894455667V',
                'contact' => '0761122335',
                'role' => 'helper',
                'assigned_line' => 'Line 2',
                'join_date' => '2023-07-20',
            ],
            [
                'name' => 'Dilshan Indika',
                'email' => 'dilshan@example.com',
                'nic' => '912233445V',
                'contact' => '0715566778',
                'role' => 'iron_operator',
                'assigned_line' => 'Line 2',
                'join_date' => '2023-08-15',
            ],
            [
                'name' => 'Sanduni Perera',
                'email' => 'sanduni@example.com',
                'nic' => '968877665V',
                'contact' => '0743322119',
                'role' => 'quality_checker',
                'assigned_line' => 'Line 2',
                'join_date' => '2023-09-01',
            ],
            [
                'name' => 'Mahesh Senanayake',
                'email' => 'mahesh@example.com',
                'nic' => '876654321V',
                'contact' => '0721155990',
                'role' => 'line_supervisor', // Updated to match valid role
                'assigned_line' => 'Line 2',
                'join_date' => '2022-10-10',
            ],
        ];

        // Add 10 General Employees
        for ($i = 1; $i <= 10; $i++) {
            $employees[] = [
                'name' => 'Employee ' . $i,
                'email' => 'emp' . $i . '@dermas.com',
                'nic' => 'EMP00' . $i . 'X',
                'contact' => '070000000' . $i,
                'role' => 'employee',
                'assigned_line' => null, // Unassigned initially
                'join_date' => '2024-01-01',
            ];
        }

        // Add 6 Line Supervisors
        for ($i = 1; $i <= 6; $i++) {
            $employees[] = [
                'name' => 'Line Supervisor ' . $i,
                'email' => 'sup' . $i . '@dermas.com',
                'nic' => 'SUP00' . $i . 'X',
                'contact' => '071111111' . $i,
                'role' => 'line_supervisor',
                'assigned_line' => null, // Unassigned initially
                'join_date' => '2024-01-01',
            ];
        }

        foreach ($employees as $emp) {
            Employee::updateOrCreate(
                ['email' => $emp['email']], // Unique check
                $emp
            );
        }
    }
}

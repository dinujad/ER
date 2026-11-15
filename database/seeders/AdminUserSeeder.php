<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = 'admin@dermasapparel.com';
        $password = Hash::make('Admin123@2026');

        $exists = DB::table('users')->where('email', $email)->exists();

        if ($exists) {
            // Update credentials on existing record
            DB::table('users')->where('email', $email)->update([
                'name'       => 'Admin User',
                'role'       => 'admin',
                'password'   => $password,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('users')->insert([
                'name'       => 'Admin User',
                'email'      => $email,
                'role'       => 'admin',
                'password'   => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("Admin user ready → {$email}");
    }
}

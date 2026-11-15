<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        if (Store::count() === 0) {
            Store::create(['name' => 'Main Store', 'address' => null]);
        }
    }
}

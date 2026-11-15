<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'line_supervisor'
            $table->string('label')->nullable(); // e.g., 'Line Supervisor'
            $table->timestamps();
        });

        // Seed initial roles
        DB::table('roles')->insert([
            ['name' => 'admin', 'label' => 'Admin'],
            ['name' => 'storekeeper', 'label' => 'Storekeeper'],
            ['name' => 'line_supervisor', 'label' => 'Line Supervisor'],
            ['name' => 'cashier', 'label' => 'Cashier'],
            ['name' => 'hr', 'label' => 'HR Manager'],
            ['name' => 'sales_agent', 'label' => 'Sales Agent'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

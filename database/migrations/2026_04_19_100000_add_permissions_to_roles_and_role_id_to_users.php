<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add JSON permissions column to roles
        Schema::table('roles', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('label');
            $table->text('description')->nullable()->after('permissions');
        });

        // Add role_id FK on users (alongside the existing string `role` column)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['permissions', 'description']);
        });
    }
};

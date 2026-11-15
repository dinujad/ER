<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'tin')) {
                $table->string('tin', 9)->nullable()->after('br_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'tin')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tin');
        });
    }
};

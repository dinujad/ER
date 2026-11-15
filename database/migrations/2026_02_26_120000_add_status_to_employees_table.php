<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'status')) {
            return;
        }
        Schema::table('employees', function (Blueprint $table) {
            $table->string('status')->default('active'); // active, resigned, etc.
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'status')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('employees', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('employees', 'effective_period_start')) {
                $table->date('effective_period_start')->nullable()->after('join_date');
            }
            if (!Schema::hasColumn('employees', 'effective_period_end')) {
                $table->date('effective_period_end')->nullable()->after('effective_period_start');
            }
            if (!Schema::hasColumn('employees', 'self_service')) {
                $table->boolean('self_service')->default(false)->after('status');
            }
            if (!Schema::hasColumn('employees', 'photo')) {
                $table->string('photo')->nullable()->after('self_service');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = ['first_name', 'last_name', 'effective_period_start', 'effective_period_end', 'self_service', 'photo'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('employees', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};

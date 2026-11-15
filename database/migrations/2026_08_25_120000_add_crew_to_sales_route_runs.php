<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_route_runs', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_route_runs', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('sales_route_runs', 'assistants')) {
                $table->text('assistants')->nullable()->after('driver_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_route_runs', function (Blueprint $table) {
            if (Schema::hasColumn('sales_route_runs', 'assistants')) {
                $table->dropColumn('assistants');
            }
            if (Schema::hasColumn('sales_route_runs', 'driver_name')) {
                $table->dropColumn('driver_name');
            }
        });
    }
};

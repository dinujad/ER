<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_issues', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->nullable()->after('quantity_issued');
            $table->decimal('line_cost', 14, 2)->nullable()->after('unit_cost');
        });

        // Backfill from current material.unit_price (purchase-driven). Portable across MySQL/SQLite.
        DB::table('material_issues')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $unit = (float) (DB::table('materials')->where('id', $row->material_id)->value('unit_price') ?? 0);
                    DB::table('material_issues')->where('id', $row->id)->update([
                        'unit_cost' => $unit,
                        'line_cost' => round((float) $row->quantity_issued * $unit, 2),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('material_issues', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'line_cost']);
        });
    }
};

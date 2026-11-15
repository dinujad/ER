<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename "Sewing / Production" to "Line Production" so supervisors see "Line Production"
     * and can record ingredients/accessories for that stage.
     */
    public function up(): void
    {
        DB::table('production_stages')
            ->where('code', 'SEWING')
            ->update(['name' => 'Line Production']);
    }

    public function down(): void
    {
        DB::table('production_stages')
            ->where('code', 'SEWING')
            ->update(['name' => 'Sewing / Production']);
    }
};

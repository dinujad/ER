<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align chart_of_accounts with Charts of Accounts UI (string GL codes, persisted opening-balance flag).
     */
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('chart_of_accounts', 'has_opening_balance')) {
                $table->boolean('has_opening_balance')->default(false)->after('opening_balance');
            }
        });

        if (Schema::hasColumn('chart_of_accounts', 'has_opening_balance')) {
            DB::table('chart_of_accounts')
                ->where(function ($q) {
                    $q->where('opening_balance', '>', 0)
                        ->orWhere('opening_balance', '<', 0);
                })
                ->update(['has_opening_balance' => true]);
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('gl_code', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->integer('gl_code')->nullable()->change();
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chart_of_accounts', 'has_opening_balance')) {
                $table->dropColumn('has_opening_balance');
            }
        });
    }
};

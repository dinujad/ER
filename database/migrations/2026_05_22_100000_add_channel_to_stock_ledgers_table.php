<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_ledgers', 'channel')) {
                $table->string('channel', 20)->default('wholesale')->after('transaction_type')->index();
            }
        });

        DB::table('stock_ledgers')->whereNull('channel')->update(['channel' => 'wholesale']);
    }

    public function down(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_ledgers', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};

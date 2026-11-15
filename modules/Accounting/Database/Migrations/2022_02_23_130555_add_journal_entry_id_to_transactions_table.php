<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJournalEntryIdToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Ultimate POS has a `transactions` table; Dermas may not. Skip when missing.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (Schema::hasColumn('transactions', 'journal_entry_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'location_id')) {
                $table->bigInteger('journal_entry_id')->unsigned()->nullable()->after('location_id');
            } else {
                $table->bigInteger('journal_entry_id')->unsigned()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'journal_entry_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('journal_entry_id');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'cheque_status')) {
                $table->string('cheque_status', 20)->nullable()->after('cheque_exception_reason');
            }
            if (!Schema::hasColumn('payments', 'cheque_bank')) {
                $table->string('cheque_bank', 100)->nullable()->after('cheque_status');
            }
            if (!Schema::hasColumn('payments', 'cheque_number')) {
                $table->string('cheque_number', 100)->nullable()->after('cheque_bank');
            }
            if (!Schema::hasColumn('payments', 'deposited_at')) {
                $table->timestamp('deposited_at')->nullable()->after('cheque_number');
            }
            if (!Schema::hasColumn('payments', 'cleared_at')) {
                $table->timestamp('cleared_at')->nullable()->after('deposited_at');
            }
            if (!Schema::hasColumn('payments', 'bounced_at')) {
                $table->timestamp('bounced_at')->nullable()->after('cleared_at');
            }
            if (!Schema::hasColumn('payments', 'bounce_reason')) {
                $table->text('bounce_reason')->nullable()->after('bounced_at');
            }
            if (!Schema::hasColumn('payments', 'receipt_journal_txn')) {
                $table->string('receipt_journal_txn', 50)->nullable()->after('bounce_reason');
            }
            if (!Schema::hasColumn('payments', 'clearance_journal_txn')) {
                $table->string('clearance_journal_txn', 50)->nullable()->after('receipt_journal_txn');
            }
        });

        // Existing cheque payments → treat as already received
        DB::table('payments')
            ->whereRaw('LOWER(TRIM(payment_method)) = ?', ['cheque'])
            ->whereNull('cheque_status')
            ->update(['cheque_status' => 'received']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $cols = [
                'cheque_status', 'cheque_bank', 'cheque_number',
                'deposited_at', 'cleared_at', 'bounced_at', 'bounce_reason',
                'receipt_journal_txn', 'clearance_journal_txn',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

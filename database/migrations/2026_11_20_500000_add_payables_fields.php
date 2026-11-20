<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6.5 — Xero-style bills: due date from supplier terms, approval for payment.
     * Confirmed bills get due date = bill date + default supplier terms (display / payment planning only).
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'due_date')) {
                $table->date('due_date')->nullable()->index();
            }
            if (!Schema::hasColumn('purchases', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('purchases', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'payment_terms_days')) {
                $table->unsignedSmallInteger('payment_terms_days')->nullable();
            }
        });

        // Bills confirmed before approvals existed count as approved.
        DB::table('purchases')->where('status', 'confirmed')->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('COALESCE(confirmed_at, updated_at, created_at)')]);

        $terms = (int) config('dermas.default_supplier_terms_days', 30);
        DB::table('purchases')
            ->whereNull('due_date')
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->select(['id', 'date'])
            ->chunkById(500, function ($rows) use ($terms) {
                foreach ($rows as $row) {
                    if ($row->date) {
                        DB::table('purchases')->where('id', $row->id)->update([
                            'due_date' => Carbon::parse($row->date)->addDays($terms)->toDateString(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            foreach (['due_date', 'approved_at', 'approved_by'] as $col) {
                if (Schema::hasColumn('purchases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'payment_terms_days')) {
                $table->dropColumn('payment_terms_days');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6.4 — Xero-style receivables: invoice date, payment terms, due date (→ Overdue), void.
     * Existing issued invoices get invoice_date = order date and due date = invoice date + default terms
     * (display / ageing only — no money changes).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_date')) {
                $table->date('invoice_date')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payment_terms_days')) {
                $table->unsignedSmallInteger('payment_terms_days')->nullable();
            }
            if (!Schema::hasColumn('orders', 'invoice_due_date')) {
                $table->date('invoice_due_date')->nullable()->index();
            }
            if (!Schema::hasColumn('orders', 'invoice_voided_at')) {
                $table->timestamp('invoice_voided_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'invoice_voided_by')) {
                $table->unsignedBigInteger('invoice_voided_by')->nullable();
            }
            if (!Schema::hasColumn('orders', 'invoice_void_reason')) {
                $table->string('invoice_void_reason', 255)->nullable();
            }
            if (!Schema::hasColumn('orders', 'last_reminder_at')) {
                $table->timestamp('last_reminder_at')->nullable();
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'payment_terms_days')) {
                $table->unsignedSmallInteger('payment_terms_days')->nullable();
            }
        });

        $defaultTerms = (int) config('dermas.default_payment_terms_days', 30);
        DB::table('orders')
            ->whereNotNull('invoice_number')
            ->whereNull('invoice_due_date')
            ->orderBy('id')
            ->select(['id', 'order_date', 'created_at'])
            ->chunkById(500, function ($rows) use ($defaultTerms) {
                foreach ($rows as $row) {
                    $date = Carbon::parse($row->order_date ?? $row->created_at ?? now())->startOfDay();
                    DB::table('orders')->where('id', $row->id)->update([
                        'invoice_date' => $date->toDateString(),
                        'invoice_due_date' => $date->copy()->addDays($defaultTerms)->toDateString(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['invoice_date', 'payment_terms_days', 'invoice_due_date', 'invoice_voided_at', 'invoice_voided_by', 'invoice_void_reason', 'last_reminder_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'payment_terms_days')) {
                $table->dropColumn('payment_terms_days');
            }
        });
    }
};

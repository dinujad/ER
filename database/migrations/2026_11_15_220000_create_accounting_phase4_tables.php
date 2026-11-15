<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Advanced reporting, analytics, tax, year-end (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->seedPhase4GlAccounts();
        $this->createReportingTables();
        $this->createForecastTables();
        $this->createTaxTables();
        $this->createYearEndTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_year_end_sessions');
        Schema::dropIfExists('acct_financial_years');
        Schema::dropIfExists('acct_tax_adjustments');
        Schema::dropIfExists('acct_tax_periods');
        Schema::dropIfExists('acct_tax_codes');
        Schema::dropIfExists('acct_cash_forecast_runs');
        Schema::dropIfExists('acct_cash_forecast_scenarios');
        Schema::dropIfExists('acct_report_executions');
        Schema::dropIfExists('acct_report_schedules');
        Schema::dropIfExists('acct_saved_reports');
    }

    private function seedPhase4GlAccounts(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        $accounts = [
            ['gl_code' => '2300', 'name' => 'Tax Payable', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['gl_code' => '1160', 'name' => 'Tax Receivable', 'account_type' => 'asset', 'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $acc) {
            $payload = array_merge($acc, [
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (Schema::hasColumn('chart_of_accounts', 'business_id')) {
                $payload['business_id'] = 0;
            }
            DB::table('chart_of_accounts')->updateOrInsert(
                ['gl_code' => $acc['gl_code']],
                $payload
            );
        }
    }

    private function createReportingTables(): void
    {
        if (! Schema::hasTable('acct_saved_reports')) {
            Schema::create('acct_saved_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('name', 160);
                $table->string('report_type', 60);
                $table->json('config')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->json('shared_with')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_report_schedules')) {
            Schema::create('acct_report_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->unsignedBigInteger('saved_report_id')->nullable();
                $table->string('report_type', 60);
                $table->string('frequency', 20);
                $table->json('config')->nullable();
                $table->json('recipients')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('last_run_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_report_executions')) {
            Schema::create('acct_report_executions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id')->nullable();
                $table->string('report_type', 60);
                $table->string('status', 20)->default('pending');
                $table->string('storage_disk', 40)->default('local');
                $table->string('storage_path', 512)->nullable();
                $table->string('format', 10)->default('csv');
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('event_key', 180)->unique();
                $table->timestamps();
            });
        }
    }

    private function createForecastTables(): void
    {
        if (! Schema::hasTable('acct_cash_forecast_scenarios')) {
            Schema::create('acct_cash_forecast_scenarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('name', 160);
                $table->string('scenario_type', 30)->default('base');
                $table->json('assumptions')->nullable();
                $table->unsignedInteger('horizon_days')->default(30);
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_cash_forecast_runs')) {
            Schema::create('acct_cash_forecast_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scenario_id');
                $table->date('as_of_date');
                $table->decimal('opening_cash', 20, 4)->default(0);
                $table->decimal('total_inflows', 20, 4)->default(0);
                $table->decimal('total_outflows', 20, 4)->default(0);
                $table->decimal('closing_cash', 20, 4)->default(0);
                $table->json('detail')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createTaxTables(): void
    {
        if (! Schema::hasTable('acct_tax_codes')) {
            Schema::create('acct_tax_codes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('code', 30);
                $table->string('name', 160);
                $table->string('tax_type', 20);
                $table->decimal('rate', 8, 4)->default(0);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('inclusive')->default(false);
                $table->unsignedBigInteger('input_account_id')->nullable();
                $table->unsignedBigInteger('output_account_id')->nullable();
                $table->unsignedBigInteger('payable_account_id')->nullable();
                $table->unsignedBigInteger('receivable_account_id')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['business_id', 'code', 'effective_from'], 'acct_tax_codes_biz_code_from_uq');
            });
        }

        if (! Schema::hasTable('acct_tax_periods')) {
            Schema::create('acct_tax_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('name', 120);
                $table->date('starts_on');
                $table->date('ends_on');
                $table->string('status', 20)->default('open');
                $table->timestamp('closed_at')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_tax_adjustments')) {
            Schema::create('acct_tax_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->unsignedBigInteger('tax_period_id');
                $table->unsignedBigInteger('tax_code_id');
                $table->decimal('amount', 20, 4);
                $table->string('direction', 10);
                $table->text('notes')->nullable();
                $table->string('journal_txn', 64)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('event_key', 180)->unique();
                $table->timestamps();
            });
        }
    }

    private function createYearEndTables(): void
    {
        if (! Schema::hasTable('acct_financial_years')) {
            Schema::create('acct_financial_years', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('name', 120);
                $table->date('starts_on');
                $table->date('ends_on');
                $table->string('status', 20)->default('open');
                $table->timestamp('closed_at')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_year_end_sessions')) {
            Schema::create('acct_year_end_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('financial_year_id');
                $table->string('status', 20)->default('draft');
                $table->json('checklist')->nullable();
                $table->string('closing_txn', 64)->nullable();
                $table->unsignedBigInteger('retained_earnings_account_id')->nullable();
                $table->json('preview')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('executed_by')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->string('event_key', 180)->unique();
                $table->timestamps();
            });
        }
    }
};
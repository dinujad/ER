<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair incomplete chart_of_accounts schemas (e.g. test stub left only id/gl_code/active).
 * Additive only — never drops financial history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('parent_id')->nullable();
                $table->text('name')->nullable();
                $table->string('gl_code', 50)->nullable();
                $table->string('account_type', 40)->default('asset');
                $table->string('normal_balance', 10)->nullable();
                $table->tinyInteger('allow_manual')->default(0);
                $table->tinyInteger('active')->default(1);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->decimal('opening_balance', 20, 4)->default(0);
                $table->boolean('has_opening_balance')->default(false);
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->unsignedBigInteger('payment_type_id')->nullable();
                $table->unsignedBigInteger('account_subtype_id')->nullable();
                $table->unsignedBigInteger('detail_type_id')->nullable();
                $table->decimal('reconcile_opening_balance', 20, 4)->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('chart_of_accounts', 'parent_id')) {
                $table->integer('parent_id')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'name')) {
                $table->text('name')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'account_type')) {
                $table->string('account_type', 40)->default('asset');
            }
            if (! Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
                $table->string('normal_balance', 10)->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'allow_manual')) {
                $table->tinyInteger('allow_manual')->default(1);
            }
            if (! Schema::hasColumn('chart_of_accounts', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'business_id')) {
                $table->unsignedBigInteger('business_id')->default(0);
            }
            if (! Schema::hasColumn('chart_of_accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 20, 4)->default(0);
            }
            if (! Schema::hasColumn('chart_of_accounts', 'has_opening_balance')) {
                $table->boolean('has_opening_balance')->default(false);
            }
            if (! Schema::hasColumn('chart_of_accounts', 'currency_id')) {
                $table->unsignedBigInteger('currency_id')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'payment_type_id')) {
                $table->unsignedBigInteger('payment_type_id')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'account_subtype_id')) {
                $table->unsignedBigInteger('account_subtype_id')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'detail_type_id')) {
                $table->unsignedBigInteger('detail_type_id')->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'reconcile_opening_balance')) {
                $table->decimal('reconcile_opening_balance', 20, 4)->nullable();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'created_at')) {
                $table->timestamps();
            }
        });

        // Ensure gl_code can hold string codes like "1000"
        try {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->string('gl_code', 50)->nullable()->change();
            });
        } catch (\Throwable $e) {
            // doctrine/dbal may be unavailable — skip change()
        }

        // Backfill names from well-known GL codes when name is empty
        $names = [
            '1000' => 'Cash',
            '1010' => 'Bank Account',
            '1150' => 'Post-Dated Cheques (PDC / Uncleared)',
            '1200' => 'Accounts Receivable',
            '1300' => 'Inventory',
            '1310' => 'Raw Material Inventory',
            '1320' => 'Work In Progress',
            '1330' => 'Finished Goods',
            '2000' => 'Accounts Payable',
            '2100' => 'Payroll Payable',
            '3000' => 'Owner Equity',
            '4000' => 'Sales Revenue',
            '4010' => 'Sales Returns',
            '5000' => 'Cost of Goods Sold',
            '5100' => 'Purchases',
            '6000' => 'General Expenses',
            '6100' => 'Salary Expense',
            '6200' => 'Utilities Expense',
            '6300' => 'Transport Expense',
            '6900' => 'Other Expenses',
        ];

        foreach ($names as $code => $name) {
            DB::table('chart_of_accounts')
                ->where('gl_code', $code)
                ->where(function ($q) {
                    $q->whereNull('name')->orWhere('name', '');
                })
                ->update([
                    'name' => $name,
                    'account_type' => match (true) {
                        str_starts_with($code, '1') => 'asset',
                        str_starts_with($code, '2') => 'liability',
                        str_starts_with($code, '3') => 'equity',
                        str_starts_with($code, '4') => 'income',
                        default => 'expense',
                    },
                    'allow_manual' => 1,
                    'active' => 1,
                ]);
        }

        // Any remaining blank names get gl_code as label
        DB::table('chart_of_accounts')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })
            ->update(['name' => DB::raw('CAST(gl_code AS CHAR)')]);
    }

    public function down(): void
    {
        // Non-destructive repair — no rollback of columns.
    }
};

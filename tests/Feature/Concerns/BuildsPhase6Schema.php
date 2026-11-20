<?php

namespace Tests\Feature\Concerns;

use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Production migrations are MySQL-specific, so Phase 6 tests build an SQLite schema:
 * ERP tables from each model's fillable columns (NUMERIC affinity keeps numbers numeric and text as text),
 * then the real Phase 6 migrations on top.
 */
trait BuildsPhase6Schema
{
    /** @var array<string, int> gl_code => chart_of_accounts.id */
    protected array $coa = [];

    protected function buildPhase6Schema(): void
    {
        $models = [
            'users' => [\App\Models\User::class, ['name', 'email', 'role', 'role_id', 'password', 'business_id']],
            'customers' => [\App\Models\Customer::class, []],
            'suppliers' => [\App\Models\Supplier::class, []],
            'products' => [\App\Models\Product::class, ['name', 'sku', 'deleted_at']],
            'orders' => [\App\Models\Order::class, ['invoice_show_sscl', 'invoice_sscl_amount', 'invoice_vat_amount']],
            'order_items' => [\App\Models\OrderItem::class, ['order_id', 'product_id', 'product_variation_id', 'quantity', 'quantity_returned', 'unit_price', 'total_price']],
            'payments' => [\App\Models\Payment::class, ['created_by', 'cheque_status', 'entry_type']],
            'sale_returns' => [\App\Models\SaleReturn::class, []],
            'sale_return_items' => [\App\Models\SaleReturnItem::class, ['sale_return_id', 'order_item_id', 'product_id', 'quantity', 'unit_price', 'line_total']],
            'purchases' => [\App\Models\Purchase::class, []],
            'purchase_items' => [\App\Models\PurchaseItem::class, ['purchase_id', 'line_total', 'tax_amount', 'is_fixed_asset', 'quantity', 'quantity_returned', 'unit_cost']],
            'purchase_payments' => [\App\Models\PurchasePayment::class, []],
            'purchase_returns' => [\App\Models\PurchaseReturn::class, []],
            'expenses' => [\App\Models\Expense::class, []],
        ];

        foreach (array_keys($models) as $table) {
            Schema::dropIfExists($table);
        }

        foreach ($models as $table => [$class, $extra]) {
            /** @var Model $model */
            $model = new $class();
            $columns = array_values(array_unique(array_merge($model->getFillable(), $extra)));
            Schema::create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $col) {
                    if (in_array($col, ['id', 'created_at', 'updated_at'], true)) {
                        continue;
                    }
                    // Identifiers that look numeric (phones, TINs, serials) must keep leading zeros.
                    if (preg_match('/(phone|tin$|vat_no|_no$|number|code|sku|reference|name|email|address|note|reason|status|type$|method|path|bank|label|txn)/', $col)) {
                        $t->string($col)->nullable();
                        continue;
                    }
                    $t->decimal($col, 20, 4)->nullable();
                }
                $t->timestamps();
            });
        }

        foreach (['journal_entries', 'accounting_postings', 'chart_of_accounts', 'audit_logs', 'stock_ledgers'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('chart_of_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('gl_code')->nullable();
            $t->string('name')->nullable();
            $t->string('account_type')->nullable();
            $t->string('normal_balance')->nullable();
            $t->unsignedBigInteger('business_id')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('chart_of_account_id');
            $t->string('transaction_number');
            $t->string('transaction_type')->nullable();
            $t->string('name')->nullable();
            $t->date('date')->nullable();
            $t->string('month')->nullable();
            $t->string('year')->nullable();
            $t->string('reference')->nullable();
            $t->string('source_type')->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->unsignedBigInteger('order_id')->nullable();
            $t->unsignedBigInteger('location_id')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('created_by_id')->nullable();
            $t->decimal('debit', 20, 4)->nullable();
            $t->decimal('credit', 20, 4)->nullable();
            $t->boolean('active')->default(true);
            $t->boolean('reversed')->default(false);
            $t->boolean('reversible')->default(true);
            $t->boolean('manual_entry')->default(false);
            $t->timestamps();
        });
        Schema::create('accounting_postings', function (Blueprint $t) {
            $t->id();
            $t->string('event_key')->unique();
            $t->string('transaction_number');
            $t->nullableMorphs('source');
            $t->string('event_type');
            $t->decimal('amount', 20, 4);
            $t->boolean('reversed')->default(false);
            $t->timestamps();
        });
        Schema::create('stock_ledgers', function (Blueprint $t) {
            $t->id();
            foreach (['product_id', 'product_variation_id', 'quantity', 'reference_id', 'created_by'] as $c) {
                $t->decimal($c, 20, 4)->nullable();
            }
            foreach (['channel', 'transaction_type', 'reference_type', 'notes'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });

        // The ERP layout (used by the 403 error page) looks up the signed-in user's employee record.
        Schema::dropIfExists('employees');
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('name')->nullable();
            $t->timestamps();
        });

        // Phase 2 tables used by reconciliation / close (shape of the production migrations).
        foreach (['bank_statement_line_matches', 'bank_statement_lines', 'bank_statement_imports', 'accounting_periods', 'accounting_audit_events'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::create('bank_statement_imports', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->default(0);
            $t->unsignedBigInteger('chart_of_account_id');
            $t->string('original_filename')->nullable();
            $t->string('status', 20)->default('preview');
            $t->unsignedBigInteger('imported_by')->nullable();
            $t->unsignedInteger('row_count')->default(0);
            $t->timestamps();
        });
        Schema::create('bank_statement_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bank_statement_import_id');
            $t->date('txn_date')->nullable();
            $t->string('description', 500)->nullable();
            $t->string('reference', 120)->nullable();
            $t->decimal('debit', 20, 4)->default(0);
            $t->decimal('credit', 20, 4)->default(0);
            $t->decimal('amount', 20, 4)->default(0);
            $t->string('fingerprint', 64);
            $t->string('match_status', 20)->default('unmatched');
            $t->string('excluded_reason')->nullable();
            $t->string('matched_source_type')->nullable();
            $t->unsignedBigInteger('matched_source_id')->nullable();
            $t->timestamp('matched_at')->nullable();
            $t->unsignedBigInteger('matched_by')->nullable();
            $t->timestamps();
            $t->unique(['bank_statement_import_id', 'fingerprint']);
        });
        Schema::create('accounting_periods', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->default(0);
            $t->string('name', 120);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('status', 20)->default('open');
            $t->timestamp('closed_at')->nullable();
            $t->unsignedBigInteger('closed_by')->nullable();
            $t->timestamps();
        });
        Schema::create('accounting_audit_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id')->default(0);
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action', 80);
            $t->string('subject_type', 120)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('before_state')->nullable();
            $t->json('after_state')->nullable();
            $t->string('reason', 500)->nullable();
            $t->timestamps();
        });

        foreach (glob(database_path('migrations/2026_11_2*_*.php')) as $file) {
            (require $file)->up();
        }

        $this->seedPhase6Coa();
    }

    protected function seedPhase6Coa(): void
    {
        $rows = [
            [AccountingService::GL_CASH, 'Cash', 'asset'],
            [AccountingService::GL_BANK, 'Bank', 'asset'],
            [AccountingService::GL_PDC_RECEIVABLE, 'PDC', 'asset'],
            [AccountingService::GL_ACCOUNTS_RECV, 'Accounts Receivable', 'asset'],
            [AccountingService::GL_TAX_RECEIVABLE, 'Tax Receivable', 'asset'],
            [AccountingService::GL_RAW_MATERIALS, 'Raw Materials', 'asset'],
            [AccountingService::GL_FINISHED_GOODS, 'Finished Goods', 'asset'],
            [AccountingService::GL_ACCOUNTS_PAY, 'Accounts Payable', 'liability'],
            [AccountingService::GL_TAX_PAYABLE, 'Tax Payable', 'liability'],
            [AccountingService::GL_SALES_REVENUE, 'Sales Revenue', 'income'],
            [AccountingService::GL_SALES_RETURNS, 'Sales Returns', 'income'],
            [AccountingService::GL_COST_OF_GOODS, 'COGS', 'expense'],
            [AccountingService::GL_GENERAL_EXPENSE, 'General Expense', 'expense'],
        ];
        foreach ($rows as [$gl, $name, $type]) {
            if (DB::table('chart_of_accounts')->where('gl_code', $gl)->exists()) {
                continue;
            }
            DB::table('chart_of_accounts')->insert([
                'gl_code' => $gl, 'name' => $name, 'account_type' => $type, 'business_id' => 0, 'active' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->coa = DB::table('chart_of_accounts')->pluck('id', 'gl_code')->map(fn ($v) => (int) $v)->all();
    }

    /** Net balance (debit − credit) of live journal lines on a GL code. */
    protected function glBalance(string $glCode): float
    {
        $id = $this->coa[$glCode] ?? DB::table('chart_of_accounts')->where('gl_code', $glCode)->value('id');
        if (!$id) {
            return 0.0;
        }

        return round((float) DB::table('journal_entries')->where('chart_of_account_id', $id)->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')->value('b'), 2);
    }
}

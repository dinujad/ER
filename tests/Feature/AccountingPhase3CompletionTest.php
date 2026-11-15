<?php

namespace Tests\Feature;

use App\Models\Accounting\FaAssetCategory;
use App\Models\Accounting\FaCapitalizationCandidate;
use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Observers\PurchaseObserver;
use App\Services\Accounting\CostCenterReportService;
use App\Services\Accounting\FixedAssetAttachmentService;
use App\Services\Accounting\FixedAssetReportService;
use App\Services\Accounting\FixedAssetService;
use App\Services\Accounting\ManufacturingOverheadAllocationService;
use App\Services\Accounting\PurchaseAssetCapitalizationService;
use App\Services\AccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountingPhase3CompletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createCompletionSchema();
        $this->seedAccounts();
    }

    private function createCompletionSchema(): void
    {
        foreach ([
            'mfg_overhead_run_lines', 'mfg_overhead_runs', 'mfg_overhead_rules',
            'fa_asset_attachments', 'fa_capitalization_candidates', 'fa_asset_events',
            'fa_depreciation_schedules', 'fa_depreciation_runs', 'fa_disposals', 'fa_assets',
            'fa_asset_categories', 'accounting_audit_events', 'accounting_postings', 'journal_entries',
            'chart_of_accounts', 'purchase_payments', 'purchase_items', 'purchases', 'materials',
            'manufacturing_job_additional_costs', 'manufacturing_jobs', 'packing_batches',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('gl_code')->unique();
            $table->string('name')->nullable();
            $table->string('account_type')->nullable();
            $table->boolean('active')->default(true);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_of_account_id');
            $table->string('transaction_number');
            $table->string('transaction_type')->nullable();
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('reference')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->decimal('debit', 20, 4)->nullable();
            $table->decimal('credit', 20, 4)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('reversed')->default(false);
            $table->boolean('reversible')->default(true);
            $table->boolean('manual_entry')->default(false);
            $table->timestamps();
        });

        Schema::create('accounting_postings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('transaction_number');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->decimal('amount', 20, 4);
            $table->boolean('reversed')->default(false);
            $table->timestamps();
        });

        Schema::create('accounting_audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('name', 160);
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->string('default_depreciation_method', 30)->default('straight_line');
            $table->unsignedInteger('default_useful_life_months')->default(12);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('entry_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('category_id');
            $table->string('asset_code', 40);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('in_service_date')->nullable();
            $table->decimal('asset_cost', 20, 4)->default(0);
            $table->decimal('capitalized_costs', 20, 4)->default(0);
            $table->decimal('net_book_value', 20, 4)->default(0);
            $table->decimal('accumulated_depreciation', 20, 4)->default(0);
            $table->decimal('salvage_value', 20, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('useful_life_months')->default(12);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('purchase_reference', 120)->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('capitalization_txn', 50)->nullable();
            $table->timestamp('capitalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_asset_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('event_type', 60);
            $table->date('event_date');
            $table->string('description', 500)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_capitalization_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0);
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('purchase_item_id')->unique();
            $table->unsignedBigInteger('fa_category_id')->nullable();
            $table->decimal('amount', 20, 4);
            $table->string('status', 30)->default('pending_review');
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('gl_treatment', 30)->default('clearing');
            $table->string('clearing_event_key', 120)->nullable();
            $table->string('capitalize_event_key', 120)->nullable();
            $table->string('capitalization_txn', 50)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('capitalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_asset_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('disk', 30)->default('local');
            $table->string('path', 500);
            $table->string('original_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 12, 2);
            $table->date('date');
            $table->string('status');
            $table->string('purchase_no')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('accounting_txn_number')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('material_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('buy_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->boolean('is_fixed_asset')->default(false);
            $table->unsignedBigInteger('fa_category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('mfg_overhead_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->unsignedBigInteger('business_id')->default(0);
            $table->string('allocation_base', 30);
            $table->decimal('rate', 20, 4)->default(0);
            $table->decimal('percentage', 10, 4)->nullable();
            $table->unsignedBigInteger('overhead_pool_account_id');
            $table->unsignedBigInteger('applied_clearing_account_id')->nullable();
            $table->unsignedBigInteger('wip_account_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('mfg_overhead_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('business_id')->default(0);
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status', 20)->default('preview');
            $table->decimal('total_base', 20, 4)->default(0);
            $table->decimal('total_allocated', 20, 4)->default(0);
            $table->string('run_key', 80)->unique();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mfg_overhead_run_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('job_id');
            $table->decimal('base_quantity', 20, 4)->default(0);
            $table->decimal('allocated_amount', 20, 4)->default(0);
            $table->string('event_key', 120)->nullable()->unique();
            $table->string('transaction_number', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('manufacturing_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('packing_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturing_job_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('manufacturing_job_additional_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturing_job_id');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('fa_depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('period_end')->nullable();
            $table->decimal('scheduled_amount', 20, 4)->default(0);
            $table->decimal('accumulated_after', 20, 4)->default(0);
            $table->decimal('nbv_after', 20, 4)->default(0);
            $table->string('status', 20)->default('scheduled');
            $table->string('transaction_number', 50)->nullable();
            $table->timestamps();
        });

        foreach (['fa_depreciation_runs', 'fa_disposals'] as $t) {
            Schema::create($t, function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }

    private function seedAccounts(): void
    {
        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'gl_code' => AccountingService::GL_RAW_MATERIALS, 'name' => 'RM', 'account_type' => 'asset', 'active' => 1],
            ['id' => 2, 'gl_code' => AccountingService::GL_ACCOUNTS_PAY, 'name' => 'AP', 'account_type' => 'liability', 'active' => 1],
            ['id' => 3, 'gl_code' => AccountingService::GL_FA_CLEARING, 'name' => 'FA Clear', 'account_type' => 'asset', 'active' => 1],
            ['id' => 4, 'gl_code' => AccountingService::GL_FIXED_ASSETS, 'name' => 'FA', 'account_type' => 'asset', 'active' => 1],
            ['id' => 5, 'gl_code' => AccountingService::GL_WORK_IN_PROGRESS, 'name' => 'WIP', 'account_type' => 'asset', 'active' => 1],
            ['id' => 6, 'gl_code' => AccountingService::GL_GENERAL_EXPENSE, 'name' => 'OH', 'account_type' => 'expense', 'active' => 1],
            ['id' => 7, 'gl_code' => AccountingService::GL_SALES_REVENUE, 'name' => 'Sales', 'account_type' => 'income', 'active' => 1],
            ['id' => 8, 'gl_code' => AccountingService::GL_SALARY_EXPENSE, 'name' => 'Sal', 'account_type' => 'expense', 'active' => 1],
        ]);

        FaAssetCategory::create([
            'code' => 'EQ',
            'name' => 'Equipment',
            'asset_account_id' => 4,
            'default_useful_life_months' => 12,
        ]);
    }

    public function test_normal_purchase_without_asset_lines_still_posts_rm_ap(): void
    {
        $purchase = Purchase::create([
            'total' => 8000,
            'date' => '2026-03-01',
            'status' => Purchase::STATUS_CONFIRMED,
            'purchase_no' => 'PO-NORMAL-1',
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => 1,
            'line_total' => 8000,
            'is_fixed_asset' => false,
        ]);

        app(PurchaseObserver::class)->backfill($purchase);

        $txn = DB::table('purchases')->where('id', $purchase->id)->value('accounting_txn_number');
        $this->assertNotEmpty($txn);
        $rows = DB::table('journal_entries')->where('transaction_number', $txn)->where('reversed', 0)->get();
        $this->assertCount(2, $rows);
        $this->assertSame(8000.0, round((float) $rows->sum('debit'), 2));
        $this->assertSame(8000.0, round((float) $rows->sum('credit'), 2));
        $this->assertSame(0, FaCapitalizationCandidate::where('purchase_id', $purchase->id)->count());
    }

    public function test_mixed_purchase_posts_rm_clearing_and_ap_balanced(): void
    {
        $purchase = Purchase::create([
            'total' => 15000,
            'date' => '2026-03-01',
            'status' => Purchase::STATUS_CONFIRMED,
            'purchase_no' => 'PO-9001',
        ]);

        PurchaseItem::create(['purchase_id' => $purchase->id, 'material_id' => 1, 'line_total' => 10000, 'is_fixed_asset' => false]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'material_id' => 1, 'line_total' => 5000, 'is_fixed_asset' => true, 'fa_category_id' => 1]);

        app(PurchaseObserver::class)->backfill($purchase);

        $txn = DB::table('purchases')->where('id', $purchase->id)->value('accounting_txn_number');
        $rows = DB::table('journal_entries')->where('transaction_number', $txn)->where('reversed', 0)->get();
        $this->assertGreaterThanOrEqual(3, $rows->count());
        $debit = round((float) $rows->sum('debit'), 2);
        $credit = round((float) $rows->sum('credit'), 2);
        $this->assertSame(15000.0, $debit);
        $this->assertSame(15000.0, $credit);

        $this->assertSame(1, FaCapitalizationCandidate::where('purchase_id', $purchase->id)->count());
    }

    public function test_capitalize_from_clearing_does_not_duplicate_ap(): void
    {
        $purchase = Purchase::create(['total' => 5000, 'date' => '2026-03-02', 'status' => Purchase::STATUS_CONFIRMED]);
        $item = PurchaseItem::create(['purchase_id' => $purchase->id, 'line_total' => 5000, 'is_fixed_asset' => true, 'fa_category_id' => 1]);
        app(PurchaseObserver::class)->backfill($purchase);
        PurchaseAssetCapitalizationService::syncCandidatesFromPurchase($purchase->fresh(['items']));

        $apCreditsBefore = DB::table('journal_entries')
            ->where('chart_of_account_id', 2)
            ->where('reversed', 0)
            ->sum('credit');

        $candidate = FaCapitalizationCandidate::where('purchase_item_id', $item->id)->firstOrFail();
        PurchaseAssetCapitalizationService::capitalize($candidate, '2026-03-05');

        $apCreditsAfter = DB::table('journal_entries')
            ->where('chart_of_account_id', 2)
            ->where('reversed', 0)
            ->sum('credit');

        $this->assertEqualsWithDelta((float) $apCreditsBefore, (float) $apCreditsAfter, 0.01);
        $this->assertSame('capitalized', $candidate->fresh()->status);
    }

    public function test_duplicate_capitalize_prevented(): void
    {
        $purchase = Purchase::create(['total' => 3000, 'date' => '2026-03-03', 'status' => Purchase::STATUS_CONFIRMED]);
        $item = PurchaseItem::create(['purchase_id' => $purchase->id, 'line_total' => 3000, 'is_fixed_asset' => true, 'fa_category_id' => 1]);
        app(PurchaseObserver::class)->backfill($purchase);
        PurchaseAssetCapitalizationService::syncCandidatesFromPurchase($purchase->fresh(['items']));
        $candidate = FaCapitalizationCandidate::where('purchase_item_id', $item->id)->firstOrFail();
        PurchaseAssetCapitalizationService::capitalize($candidate, '2026-03-05');

        $this->expectException(\RuntimeException::class);
        PurchaseAssetCapitalizationService::capitalize($candidate->fresh(), '2026-03-06');
    }

    public function test_cost_center_pl_income_expense_and_unassigned(): void
    {
        $txn = AccountingService::postMultiLine([
            ['account_id' => 8, 'debit' => 500, 'credit' => 0],
            ['account_id' => 7, 'debit' => 0, 'credit' => 500],
        ], '2026-04-01', 'Unassigned revenue', 'X', 'manual', ['cost_center_id' => null]);

        $assignedTxn = AccountingService::postMultiLine([
            ['account_id' => 8, 'debit' => 200, 'credit' => 0],
            ['account_id' => 7, 'debit' => 0, 'credit' => 200],
        ], '2026-04-02', 'CC revenue', 'Y', 'manual', ['cost_center_id' => 5]);

        DB::table('journal_entries')->where('transaction_number', $assignedTxn)->update(['cost_center_id' => 5]);

        $unassigned = CostCenterReportService::profitAndLoss(null, '2026-04-01', '2026-04-30');
        $this->assertSame('500.00', $unassigned['income']);
        $this->assertSame('500.00', $unassigned['expense']);

        $assigned = CostCenterReportService::profitAndLoss(5, '2026-04-01', '2026-04-30');
        $this->assertSame('200.00', $assigned['income']);
    }

    public function test_overhead_production_qty_allocation_and_duplicate_run_prevention(): void
    {
        $rule = ManufacturingOverheadAllocationService::createRule([
            'name' => 'Qty rule',
            'allocation_base' => 'production_qty',
            'rate' => 2,
            'overhead_pool_account_id' => 6,
            'wip_account_id' => 5,
            'effective_from' => '2026-01-01',
        ]);
        ManufacturingOverheadAllocationService::approveRule($rule);
        ManufacturingOverheadAllocationService::activateRule($rule);

        $job = ManufacturingJob::create(['job_number' => 'J1', 'issue_date' => '2026-02-15', 'status' => 'open']);
        DB::table('packing_batches')->insert(['manufacturing_job_id' => $job->id, 'quantity' => 100, 'created_at' => now(), 'updated_at' => now()]);

        $preview = ManufacturingOverheadAllocationService::preview($rule, '2026-02-01', '2026-02-28');
        $this->assertSame(200.0, (float) $preview['run']->total_allocated);

        $posted = ManufacturingOverheadAllocationService::postRun($preview['run']);
        $this->assertSame('posted', $posted->status);

        $this->expectException(\RuntimeException::class);
        ManufacturingOverheadAllocationService::preview($rule, '2026-02-01', '2026-02-28');
    }

    public function test_attachment_rejects_bad_mime(): void
    {
        Storage::fake('local');
        $asset = FixedAssetService::createDraft(['category_id' => 1, 'name' => 'Cam', 'asset_cost' => 1000]);
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

        $this->expectException(\RuntimeException::class);
        FixedAssetAttachmentService::store($asset, $file, [], 1);
    }

    public function test_export_register_totals_match_service(): void
    {
        FixedAssetService::createDraft(['category_id' => 1, 'name' => 'A1', 'asset_cost' => 1000]);
        FixedAssetService::createDraft(['category_id' => 1, 'name' => 'A2', 'asset_cost' => 2500.5]);

        $report = FixedAssetReportService::register();
        $this->assertSame('3500.50', $report['totals']['cost']);
        $this->assertCount(2, $report['rows']);
    }
}
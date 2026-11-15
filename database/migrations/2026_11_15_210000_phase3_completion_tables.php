<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 completion — purchase capitalization, attachments, overhead allocation (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->seedFaClearingGl();
        $this->extendPurchaseItems();
        $this->extendFaAssets();
        $this->createCapitalizationCandidates();
        $this->createFaAttachments();
        $this->createOverheadTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('mfg_overhead_run_lines');
        Schema::dropIfExists('mfg_overhead_runs');
        Schema::dropIfExists('mfg_overhead_rules');
        Schema::dropIfExists('fa_asset_attachments');
        Schema::dropIfExists('fa_capitalization_candidates');

        if (Schema::hasTable('fa_assets')) {
            Schema::table('fa_assets', function (Blueprint $table) {
                foreach (['purchase_id', 'purchase_item_id'] as $col) {
                    if (Schema::hasColumn('fa_assets', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                foreach ([
                    'is_fixed_asset', 'fa_category_id', 'fa_useful_life_months',
                    'fa_depreciation_method', 'fa_salvage_value', 'fa_in_service_date', 'fa_cost_center_id',
                ] as $col) {
                    if (Schema::hasColumn('purchase_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function seedFaClearingGl(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) {
            return;
        }

        DB::table('chart_of_accounts')->updateOrInsert(
            ['gl_code' => '1410', 'business_id' => 0],
            [
                'gl_code' => '1410',
                'business_id' => 0,
                'name' => 'Fixed Asset Clearing',
                'account_type' => 'asset',
                'normal_balance' => 'debit',
                'active' => 1,
                'allow_manual' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function extendPurchaseItems(): void
    {
        if (! Schema::hasTable('purchase_items')) {
            return;
        }

        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'is_fixed_asset')) {
                $table->boolean('is_fixed_asset')->default(false)->after('line_total');
            }
            if (! Schema::hasColumn('purchase_items', 'fa_category_id')) {
                $table->unsignedBigInteger('fa_category_id')->nullable()->after('is_fixed_asset');
            }
            if (! Schema::hasColumn('purchase_items', 'fa_useful_life_months')) {
                $table->unsignedInteger('fa_useful_life_months')->nullable()->after('fa_category_id');
            }
            if (! Schema::hasColumn('purchase_items', 'fa_depreciation_method')) {
                $table->string('fa_depreciation_method', 30)->nullable()->after('fa_useful_life_months');
            }
            if (! Schema::hasColumn('purchase_items', 'fa_salvage_value')) {
                $table->decimal('fa_salvage_value', 20, 4)->nullable()->after('fa_depreciation_method');
            }
            if (! Schema::hasColumn('purchase_items', 'fa_in_service_date')) {
                $table->date('fa_in_service_date')->nullable()->after('fa_salvage_value');
            }
            if (! Schema::hasColumn('purchase_items', 'fa_cost_center_id')) {
                $table->unsignedBigInteger('fa_cost_center_id')->nullable()->after('fa_in_service_date');
            }
        });
    }

    private function extendFaAssets(): void
    {
        if (! Schema::hasTable('fa_assets')) {
            return;
        }

        Schema::table('fa_assets', function (Blueprint $table) {
            if (! Schema::hasColumn('fa_assets', 'purchase_id')) {
                $table->unsignedBigInteger('purchase_id')->nullable()->index()->after('purchase_reference');
            }
            if (! Schema::hasColumn('fa_assets', 'purchase_item_id')) {
                $table->unsignedBigInteger('purchase_item_id')->nullable()->index()->after('purchase_id');
            }
        });
    }

    private function createCapitalizationCandidates(): void
    {
        if (Schema::hasTable('fa_capitalization_candidates')) {
            return;
        }

        Schema::create('fa_capitalization_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(0)->index();
            $table->unsignedBigInteger('purchase_id')->index();
            $table->unsignedBigInteger('purchase_item_id')->unique();
            $table->unsignedBigInteger('fa_category_id')->nullable();
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedBigInteger('asset_id')->nullable()->index();
            $table->string('gl_treatment', 30)->default('clearing');
            $table->string('clearing_event_key', 120)->nullable();
            $table->string('capitalize_event_key', 120)->nullable();
            $table->string('capitalization_txn', 50)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('capitalized_at')->nullable();
            $table->timestamps();
        });
    }

    private function createFaAttachments(): void
    {
        if (Schema::hasTable('fa_asset_attachments')) {
            return;
        }

        Schema::create('fa_asset_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->index();
            $table->string('disk', 30)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('description', 500)->nullable();
            $table->string('document_type', 60)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'document_type']);
        });
    }

    private function createOverheadTables(): void
    {
        if (! Schema::hasTable('mfg_overhead_rules')) {
            Schema::create('mfg_overhead_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('allocation_base', 30);
                $table->decimal('rate', 20, 4)->default(0);
                $table->decimal('percentage', 10, 4)->nullable();
                $table->unsignedBigInteger('overhead_pool_account_id');
                $table->unsignedBigInteger('applied_clearing_account_id')->nullable();
                $table->unsignedBigInteger('wip_account_id')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mfg_overhead_runs')) {
            Schema::create('mfg_overhead_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id')->index();
                $table->unsignedBigInteger('business_id')->default(0)->index();
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
        }

        if (! Schema::hasTable('mfg_overhead_run_lines')) {
            Schema::create('mfg_overhead_run_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('run_id')->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->decimal('base_quantity', 20, 4)->default(0);
                $table->decimal('allocated_amount', 20, 4)->default(0);
                $table->string('event_key', 120)->nullable()->unique();
                $table->string('transaction_number', 50)->nullable();
                $table->timestamps();
            });
        }
    }
};
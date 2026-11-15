<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingPhase3WorkspaceUiTest extends TestCase
{
    private function viewer(): User
    {
        $user = new User(['role' => 'staff', 'role_id' => 1, 'name' => 'Acct Viewer']);
        $user->setRelation('roleModel', new Role(['name' => 'staff', 'permissions' => ['accounting.view']]));

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePhase3UiTables();
    }

    private function ensurePhase3UiTables(): void
    {
        if (! Schema::hasTable('fa_asset_categories')) {
            Schema::create('fa_asset_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('code', 30);
                $table->string('name', 160);
                $table->string('default_depreciation_method', 30)->default('straight_line');
                $table->unsignedInteger('default_useful_life_months')->default(12);
                $table->decimal('default_salvage_value', 20, 4)->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fa_assets')) {
            Schema::create('fa_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->unsignedBigInteger('category_id')->default(1);
                $table->string('asset_code', 40)->nullable();
                $table->string('name', 200)->nullable();
                $table->decimal('asset_cost', 20, 4)->default(0);
                $table->decimal('capitalized_costs', 20, 4)->default(0);
                $table->decimal('net_book_value', 20, 4)->default(0);
                $table->decimal('accumulated_depreciation', 20, 4)->default(0);
                $table->string('status', 30)->default('draft');
                $table->string('depreciation_method', 30)->default('straight_line');
                $table->unsignedInteger('useful_life_months')->default(12);
                $table->decimal('salvage_value', 20, 4)->default(0);
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            'fa_asset_events' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asset_id');
                $table->timestamps();
            },
            'fa_depreciation_schedules' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asset_id');
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->decimal('scheduled_amount', 20, 4)->default(0);
                $table->string('status', 20)->default('scheduled');
                $table->timestamps();
            },
            'fa_depreciation_runs' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->date('run_date')->nullable();
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->string('status', 20)->default('posted');
                $table->decimal('total_amount', 20, 4)->default(0);
                $table->unsignedInteger('asset_count')->default(0);
                $table->timestamps();
            },
            'fa_disposals' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asset_id');
                $table->timestamps();
            },
            'acct_loans' => function (Blueprint $table) {
                $table->id();
                $table->string('loan_number', 40)->nullable();
                $table->string('lender_name', 160)->nullable();
                $table->decimal('principal', 20, 4)->default(0);
                $table->decimal('outstanding_principal', 20, 4)->default(0);
                $table->decimal('annual_interest_rate', 8, 4)->default(0);
                $table->unsignedInteger('term_months')->default(12);
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            },
            'acct_loan_schedule_lines' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id');
                $table->timestamps();
            },
            'acct_loan_payments' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id');
                $table->timestamps();
            },
            'acct_cost_centers' => function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->nullable();
                $table->string('name', 160)->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();
            },
            'acct_cost_allocations' => function (Blueprint $table) {
                $table->id();
                $table->string('name', 160)->nullable();
                $table->date('allocation_date')->nullable();
                $table->decimal('source_amount', 20, 4)->default(0);
                $table->string('status', 20)->default('posted');
                $table->timestamps();
            },
            'acct_cost_allocation_lines' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('allocation_id');
                $table->timestamps();
            },
            'acct_budget_versions' => function (Blueprint $table) {
                $table->id();
                $table->string('name', 160)->nullable();
                $table->string('financial_year', 10)->nullable();
                $table->unsignedInteger('version_no')->default(1);
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            },
            'acct_budget_version_lines' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('budget_version_id');
                $table->unsignedBigInteger('chart_of_account_id');
                $table->decimal('annual_total', 20, 4)->default(0);
                for ($m = 1; $m <= 12; $m++) {
                    $table->decimal('month_'.$m, 20, 4)->default(0);
                }
                $table->timestamps();
            },
            'manufacturing_jobs' => function (Blueprint $table) {
                $table->id();
                $table->string('job_number')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            },
        ] as $name => $callback) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, $callback);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function phase3ReadRoutes(): array
    {
        return [
            'accounting/fixed-assets',
            'accounting/fixed-asset-categories',
            'accounting/depreciation/runs',
            'accounting/depreciation/preview',
            'accounting/asset-disposals',
            'accounting/loans',
            'accounting/cost-centers',
            'accounting/cost-allocations',
            'accounting/job-costs',
            'accounting/manufacturing-accounting',
            'accounting/budget-versions',
            'accounting/budget-versions/vs-actual',
        ];
    }

    public function test_authorized_user_can_open_phase3_workspace_pages(): void
    {
        $this->actingAs($this->viewer());

        foreach ($this->phase3ReadRoutes() as $uri) {
            $response = $this->get('/'.$uri);
            $this->assertContains($response->status(), [200, 302], 'GET /'.$uri.' failed with '.$response->status());
        }
    }

    public function test_phase3_sidebar_links_render_on_dashboard(): void
    {
        $this->actingAs($this->viewer());
        $response = $this->get('/accounting/dashboard');
        $response->assertOk();
        $response->assertSee('Asset Register', false);
        $response->assertSee('Loan Register', false);
        $response->assertSee('Budget Versions', false);
        $response->assertSee('Production Costing', false);
    }
}
<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\AccountingWorkspaceMetrics;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingWorkspaceUiTest extends TestCase
{
    private function userWithPermissions(array $permissions = [], string $role = 'staff'): User
    {
        $user = new User(['role' => $role, 'role_id' => 1, 'name' => 'Acct User']);
        $user->setRelation('roleModel', new Role(['name' => $role, 'permissions' => $permissions]));

        return $user;
    }

    public function test_guest_is_redirected_from_accounting_dashboard(): void
    {
        $this->get('/accounting/dashboard')->assertRedirect();
    }

    public function test_user_without_accounting_view_cannot_open_workspace(): void
    {
        $this->actingAs($this->userWithPermissions(['expenses.view']));
        $this->get('/accounting/dashboard')->assertForbidden();
    }

    public function test_authorized_user_sees_workspace_shell_and_back_to_erp(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view']));

        $response = $this->get('/accounting/dashboard');
        $response->assertOk();
        $response->assertSee('acct-workspace', false);
        $response->assertSee('Back to ERP', false);
        $response->assertSee('acct-ws-sidebar', false);
        $response->assertSee('LKR', false);
        $response->assertSee('/erp/dashboard', false);
    }

    public function test_accounting_root_redirects_to_dashboard(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view']));
        $this->get('/accounting')->assertRedirect('/accounting/dashboard');
    }

    public function test_authorized_user_can_open_phase4_routes(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view']));
        $this->get('/accounting/phase4/statements/profit-and-loss')->assertOk();
        $this->get('/accounting/phase4/kpi-analytics')->assertOk();
        $this->get('/accounting/phase4/tax')->assertOk();
    }

    public function test_workspace_metrics_use_journal_balances_not_placeholders(): void
    {
        Schema::dropIfExists('accounting_postings');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');

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
            $table->nullableMorphs('source');
            $table->string('event_type');
            $table->decimal('amount', 20, 4);
            $table->boolean('reversed')->default(false);
            $table->timestamps();
        });

        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'gl_code' => AccountingService::GL_CASH, 'name' => 'Cash', 'account_type' => 'asset', 'active' => 1],
            ['id' => 2, 'gl_code' => AccountingService::GL_ACCOUNTS_RECV, 'name' => 'AR', 'account_type' => 'asset', 'active' => 1],
            ['id' => 3, 'gl_code' => AccountingService::GL_SALES_REVENUE, 'name' => 'Sales', 'account_type' => 'income', 'active' => 1],
            ['id' => 4, 'gl_code' => AccountingService::GL_GENERAL_EXPENSE, 'name' => 'Expense', 'account_type' => 'expense', 'active' => 1],
            ['id' => 5, 'gl_code' => AccountingService::GL_BANK, 'name' => 'Bank', 'account_type' => 'asset', 'active' => 1],
            ['id' => 6, 'gl_code' => AccountingService::GL_ACCOUNTS_PAY, 'name' => 'AP', 'account_type' => 'liability', 'active' => 1],
        ]);

        AccountingService::postOnce('order:1:sale', 'order', 1, 2, 3, 1000, '2026-11-01', 'Sale', 'ORD-1', 'sale');
        AccountingService::postOnce('expense:1:entry', 'expense', 1, 4, 1, 200, '2026-11-01', 'Exp', 'EXP-1', 'expense');

        $summary = app(AccountingWorkspaceMetrics::class)->summary('2026-11-01', '2026-11-30');

        $this->assertTrue($summary['available']);
        $this->assertEquals(1000.0, $summary['revenue']);
        $this->assertEquals(200.0, $summary['expenses']);
        $this->assertEquals(800.0, $summary['net_profit']);
        $this->assertEquals(-200.0, $summary['cash']);
        $this->assertEquals(1000.0, $summary['receivables']);
    }
}

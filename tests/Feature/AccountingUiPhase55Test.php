<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AccountingUiPhase55Test extends TestCase
{
    private function userWithPermissions(array $permissions = [], string $role = 'staff'): User
    {
        $user = new User(['role' => $role, 'role_id' => 1, 'name' => 'Acct User']);
        $user->setRelation('roleModel', new Role(['name' => $role, 'permissions' => $permissions]));

        return $user;
    }

    public function test_sidebar_has_collapsible_groups_and_search(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        $response = $this->get('/accounting/dashboard');
        $response->assertOk();
        $response->assertSee('acct-ws-group', false);
        $response->assertSee('acctNavSearch', false);
        $response->assertSee('Cost Accounting', false);
        $response->assertSee('AI &amp; Automation', false);
        $response->assertSee('Back to ERP', false);
        $response->assertSee('--acct-navy', false);
        $response->assertSee('acct-kpi', false);
        $response->assertSee('Quick actions', false);
    }

    public function test_cost_center_pl_page_renders_premium_layout(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        $response = $this->get('/accounting/cost-center-reports/profit-and-loss?cost_center_id=all');
        $response->assertOk();
        $response->assertSee('Cost Center Profit &amp; Loss', false);
        $response->assertSee('acct-kpi-grid', false);
        $response->assertSee('All (company-wide)', false);
        $response->assertSee('Profit Margin', false);
    }

    public function test_phase5_pages_still_render_under_new_shell(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        foreach ([
            '/accounting/phase5/assistant',
            '/accounting/phase5/ocr',
            '/accounting/phase5/alerts',
            '/accounting/phase5/review',
            '/accounting/phase4/statements/profit-and-loss',
            '/accounting/bank-matching',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_advanced_statement_pages_render_with_shell(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        foreach ([
            '/accounting/phase4/statements/profit-and-loss',
            '/accounting/phase4/statements/balance-sheet',
            '/accounting/phase4/statements/cash-flow',
            '/accounting/phase4/statements/changes-in-equity',
            '/accounting/phase4/statements/trial-balance',
            '/accounting/phase4/kpi-analytics',
        ] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee('acct-adv-report', false);
            $response->assertSee('acct-kpi-grid', false);
        }
    }

    public function test_statement_exports_download(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        $pdf = $this->get('/accounting/phase4/statements/profit-and-loss/pdf?from=2026-01-01&to=2026-12-31');
        $pdf->assertOk();
        $this->assertStringContainsString('pdf', strtolower($pdf->headers->get('content-type', '')));

        $xlsx = $this->get('/accounting/phase4/statements/trial-balance/xlsx?from=2026-01-01&to=2026-12-31');
        $xlsx->assertOk();
    }
}

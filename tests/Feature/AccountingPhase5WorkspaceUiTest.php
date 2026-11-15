<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AccountingPhase5WorkspaceUiTest extends TestCase
{
    private function userWithPermissions(array $permissions = [], string $role = 'staff'): User
    {
        $user = new User(['role' => $role, 'role_id' => 1, 'name' => 'Acct User']);
        $user->setRelation('roleModel', new Role(['name' => $role, 'permissions' => $permissions]));

        return $user;
    }

    public function test_phase5_sidebar_and_pages_render(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        $dash = $this->get('/accounting/dashboard');
        $dash->assertOk();
        $dash->assertSee('AI Accounting Assistant', false);
        $dash->assertSee('Smart Document Capture', false);
        $dash->assertSee('AI Review Center', false);

        foreach ([
            '/accounting/phase5/assistant',
            '/accounting/phase5/ocr',
            '/accounting/phase5/categorization',
            '/accounting/phase5/alerts',
            '/accounting/phase5/insights',
            '/accounting/phase5/review',
            '/accounting/phase5/automation',
            '/accounting/phase5/automation/history',
            '/accounting/phase5/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}

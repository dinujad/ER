<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * Ensures every workspace sidebar href maps to a registered GET route (no dead links).
 * Runtime MySQL smoke is covered by scripts/smoke_sidebar_http.php.
 */
class AccountingSidebarNavSmokeTest extends TestCase
{
    private function userWithPermissions(array $permissions = []): User
    {
        $user = new User(['role' => 'staff', 'role_id' => 1, 'name' => 'Acct User']);
        $user->setRelation('roleModel', new Role(['name' => 'staff', 'permissions' => $permissions]));

        return $user;
    }

    public function test_sidebar_urls_are_registered_routes(): void
    {
        $html = file_get_contents(base_path('modules/Accounting/Resources/views/layouts/workspace_sidebar.blade.php'));
        preg_match_all("/url\\('([^']+)'\\)/", $html, $m);
        $paths = array_values(array_unique($m[1]));

        $routeUris = collect(app('router')->getRoutes())->map(fn ($r) => '/'.ltrim($r->uri(), '/'))->all();
        $missing = [];
        foreach ($paths as $raw) {
            $path = '/'.ltrim(preg_replace('/\\?.*/', '', $raw), '/');
            $found = false;
            foreach ($routeUris as $uri) {
                $pattern = '#^'.str_replace('/', '\\/', preg_replace('/\\{[^}]+\\}/', '[^/]+', $uri)).'$#';
                if (preg_match($pattern, $path)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $missing[] = $path;
            }
        }

        $this->assertSame([], $missing, 'Unregistered sidebar routes: '.implode(', ', $missing));
    }

    public function test_core_workspace_pages_render(): void
    {
        $this->actingAs($this->userWithPermissions(['accounting.view', 'accounting.manage']));

        foreach ([
            '/accounting/dashboard',
            '/accounting/phase4/statements/profit-and-loss',
            '/accounting/cost-center-reports/profit-and-loss',
            '/accounting/phase5/assistant',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}

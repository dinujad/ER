<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\ActionPermissions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActionPermissionsTest extends TestCase
{
    private function userWithPermissions(array $permissions = [], string $name = 'staff'): User
    {
        $user = new User(['role' => $name, 'role_id' => 1]);
        $user->setRelation('roleModel', new Role(['name' => $name, 'permissions' => $permissions]));

        return $user;
    }

    public function test_view_and_create_permissions_never_grant_edit_or_delete(): void
    {
        $user = $this->userWithPermissions(Role::allPermissionKeys());
        $user->roleModel->permissions = array_values(array_filter(
            Role::allPermissionKeys(),
            fn ($key) => !str_ends_with($key, '.edit') && !str_ends_with($key, '.delete')
        ));
        $count = 0;
        foreach (Route::getRoutes() as $route) {
            if (ActionPermissions::required($route)) {
                $this->assertFalse(ActionPermissions::allows($user, $route), $route->getName() ?? $route->uri());
                $count++;
            }
        }
        $this->assertGreaterThan(50, $count);
    }

    public function test_edit_and_delete_are_independent_explicit_grants(): void
    {
        $edit = Route::getRoutes()->getByName('products.edit');
        $delete = Route::getRoutes()->getByName('products.destroy');

        $editor = $this->userWithPermissions(['products.edit']);
        $this->assertTrue(ActionPermissions::allows($editor, $edit));
        $this->assertFalse(ActionPermissions::allows($editor, $delete));

        $deleter = $this->userWithPermissions(['products.delete']);
        $this->assertFalse(ActionPermissions::allows($deleter, $edit));
        $this->assertTrue(ActionPermissions::allows($deleter, $delete));
        $this->assertFalse(ActionPermissions::allowsRoute($deleter, 'employees.destroy'));
    }

    public function test_legacy_and_assigned_admins_have_full_action_access(): void
    {
        foreach ([new User(['role' => 'admin']), $this->userWithPermissions([], 'admin')] as $admin) {
            $this->assertTrue($admin->isSystemAdmin());
            foreach (['employees.bulk-delete', 'products.edit', 'driver.delete-item', 'roles.update'] as $name) {
                $this->assertTrue(ActionPermissions::allowsRoute($admin, $name));
            }
        }
        $restricted = $this->userWithPermissions();
        $restricted->role = 'admin';
        $this->assertFalse($restricted->isSystemAdmin());
    }

    public function test_direct_requests_are_denied_before_binding_or_modifying_records(): void
    {
        $this->actingAs($this->userWithPermissions(['products.view', 'employees.create', 'expenses.view']));

        $this->get('/erp/products/999/edit')->assertForbidden();
        $this->put('/erp/products/999', ['name' => 'Changed'])->assertForbidden();
        $this->delete('/erp/products/999')->assertForbidden();
        $this->post('/erp/employees/bulk-delete', ['ids' => [999]])->assertForbidden();
        $this->get('/erp/expenses/clear-all')->assertForbidden();
    }

    public function test_explicit_permissions_pass_the_web_and_erp_checks(): void
    {
        // Probe routes run the same middleware as ERP routes without touching a database.
        Route::middleware(['web', 'auth', 'erp.permission'])->group(function () {
            Route::put('/permission-test/edit', fn () => response('edited'))->name('inventory.test.update');
            Route::delete('/permission-test/delete', fn () => response('deleted'))->name('inventory.test.destroy');
        });
        $this->actingAs($this->userWithPermissions(['inventory.edit']));
        $this->put('/permission-test/edit')->assertOk()->assertSee('edited');
        $this->delete('/permission-test/delete')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['inventory.delete']));
        $this->put('/permission-test/edit')->assertForbidden();
        $this->delete('/permission-test/delete')->assertOk()->assertSee('deleted');
    }

    public function test_portal_users_cannot_delete_their_own_records_without_permission(): void
    {
        $this->actingAs($this->userWithPermissions([], 'sales_person'));
        $this->delete('/sales-person/expenses/999')->assertForbidden();

        $this->actingAs($this->userWithPermissions([], 'driver'));
        $this->delete('/driver/items/999')->assertForbidden();
        $this->put('/driver/jobs/999')->assertForbidden();
    }

    public function test_role_permissions_cannot_be_granted_by_non_admins(): void
    {
        $this->actingAs($this->userWithPermissions(Role::allPermissionKeys()));
        $this->get('/erp/roles/create')->assertForbidden();
        $this->post('/erp/roles', ['label' => 'New role', 'permissions' => ['products.delete']])->assertForbidden();
        $this->put('/erp/roles/999', ['permissions' => ['products.delete']])->assertForbidden();
    }

    public function test_action_controls_follow_the_same_permission_checks(): void
    {
        $template = "@canRoute('products.edit')\nEdit control\n@endcanRoute\n@canRoute('products.destroy')\nDelete control\n@endcanRoute\n";
        $this->actingAs($this->userWithPermissions(['products.view', 'products.create']));
        $html = Blade::render($template);
        $this->assertStringNotContainsString('Edit control', $html);
        $this->assertStringNotContainsString('Delete control', $html);

        $this->actingAs($this->userWithPermissions(['products.edit']));
        $html = Blade::render($template);
        $this->assertStringContainsString('Edit control', $html);
        $this->assertStringNotContainsString('Delete control', $html);
    }
}

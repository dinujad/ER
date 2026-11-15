<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolePermissionSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label')->unique();
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();
        });
        $this->actingAs(new User(['role' => 'admin']));
    }

    public function test_admin_can_select_and_remove_edit_and_delete_permissions(): void
    {
        $this->post('/erp/roles', [
            'label' => 'Warehouse Viewer', 'permissions' => ['products.view'],
        ])->assertRedirect(route('roles.index'));
        $role = Role::sole();
        $this->assertSame(['products.view'], $role->permissions);
        $this->assertFalse($role->hasPermission('products.edit'));
        $this->assertFalse($role->hasPermission('products.delete'));

        $this->put('/erp/roles/'.$role->id, [
            'label' => $role->label,
            'permissions' => ['products.view', 'products.edit', 'products.delete'],
        ])->assertRedirect(route('roles.index'));
        $role->refresh();
        $this->assertTrue($role->hasPermission('products.edit'));
        $this->assertTrue($role->hasPermission('products.delete'));

        $this->put('/erp/roles/'.$role->id, ['label' => $role->label])->assertRedirect(route('roles.index'));
        $this->assertSame([], $role->fresh()->permissions);
    }

    public function test_unknown_permission_keys_are_rejected(): void
    {
        $this->postJson('/erp/roles', [
            'label' => 'Unknown Access', 'permissions' => ['admin.only'],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions.0');
        $this->assertSame(0, Role::count());
    }

    public function test_non_admin_user_editor_cannot_assign_an_admin_role(): void
    {
        $role = Role::create(['name' => 'editor', 'label' => 'Editor', 'permissions' => ['users.edit']]);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $editor = User::create([
            'name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'password',
            'role' => 'editor', 'role_id' => $role->id,
        ]);
        $editor->setRelation('employee', null);
        $target = User::create([
            'name' => 'Staff', 'email' => 'staff@example.test', 'password' => 'password',
            'role' => 'staff',
        ]);
        $this->actingAs($editor);

        $this->put('/erp/users/'.$target->id, [
            'name' => $target->name, 'email' => $target->email, 'role_id' => $adminRole->id,
        ])->assertForbidden();
        $this->assertNull($target->fresh()->role_id);

        $this->put('/erp/users/'.$target->id, [
            'name' => 'Updated Staff', 'email' => $target->email,
        ])->assertRedirect(route('erp.users.index'));
        $this->assertSame('Updated Staff', $target->fresh()->name);
        $this->assertNull($target->fresh()->role_id);
    }

    public function test_role_form_lists_separate_unchecked_edit_and_delete_options(): void
    {
        $html = view('erp.roles._permission_form', [
            'permissionGroups' => config('permissions'), 'selectedPerms' => ['products.view'],
        ])->render();
        $this->assertStringContainsString('value="products.edit"', $html);
        $this->assertStringContainsString('value="employees.delete"', $html);
        $this->assertMatchesRegularExpression('/value="products.edit"[^>]*\s>/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="products.edit"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="employees.delete"[^>]*checked/', $html);
    }
}

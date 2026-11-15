<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('label')->get();
        $permissionGroups = config('permissions');
        return view('erp.roles.index', compact('roles', 'permissionGroups'));
    }

    public function create()
    {
        $permissionGroups = config('permissions');
        return view('erp.roles.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'       => 'required|string|max:100|unique:roles,label',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(Role::allPermissionKeys())],
        ]);

        $name = Str::slug($request->label, '_');
        abort_if($name === 'admin' || Role::where('name', $name)->exists(), 422, 'This role name is reserved or already in use.');

        Role::create([
            'name'        => $name,
            'label'       => $request->label,
            'description' => $request->description,
            'permissions' => $request->input('permissions', []),
        ]);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissionGroups = config('permissions');
        return view('erp.roles.edit', compact('role', 'permissionGroups'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'label'       => 'required|string|max:100|unique:roles,label,' . $role->id,
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(Role::allPermissionKeys())],
        ]);

        // Admin role always has all permissions
        $permissions = $role->isAdmin() ? Role::allPermissionKeys() : $request->input('permissions', []);

        $role->update([
            'label'       => $request->label,
            'description' => $request->description,
            'permissions' => $permissions,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->isAdmin()) {
            return back()->withErrors(['error' => 'The Admin role cannot be deleted.']);
        }
        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete a role that has users assigned to it.']);
        }
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }
}

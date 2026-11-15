<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roleModel')->orderBy('name')->get();
        return view('erp.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('label')->get();
        return view('erp.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            'role_id'               => 'nullable|exists:roles,id',
        ]);

        abort_if(!Auth::user()->isSystemAdmin() && !empty($data['role_id']), 403,
            'Only administrators can assign roles.');
        $role = !empty($data['role_id']) ? Role::find($data['role_id']) : null;

        // If a specific role is assigned, use that role's name as the legacy role string.
        // Only use 'admin' when explicitly assigning the admin role; default to 'staff' otherwise.
        $legacyRole = $role ? ($role->isAdmin() ? 'admin' : $role->name) : 'staff';

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => $data['role_id'] ?? null,
            'role'     => $legacyRole,
        ]);

        return redirect()->route('erp.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        abort_if($user->isSystemAdmin() && !Auth::user()->isSystemAdmin(), 403);
        $roles = Role::orderBy('label')->get();
        return view('erp.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id'  => 'nullable|exists:roles,id',
        ]);

        if (!Auth::user()->isSystemAdmin()) {
            abort_if($user->isSystemAdmin(), 403);
            abort_if(array_key_exists('role_id', $data) && (int) $data['role_id'] !== (int) $user->role_id,
                403, 'Only administrators can change roles.');
            $data['role_id'] = $user->role_id;
        }
        $role = !empty($data['role_id']) ? Role::find($data['role_id']) : null;
        $legacyRole = $role ? ($role->isAdmin() ? 'admin' : $role->name) : 'staff';

        $update = [
            'name'    => $data['name'],
            'email'   => $data['email'],
            'role_id' => $data['role_id'] ?? null,
            'role'    => $legacyRole,
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return redirect()->route('erp.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if($user->isSystemAdmin() && !Auth::user()->isSystemAdmin(), 403);
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }
        $user->delete();
        return redirect()->route('erp.users.index')->with('success', 'User deleted.');
    }
}

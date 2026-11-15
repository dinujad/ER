<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ProductionSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Admin: list section supervisors and set their login password.
 */
class SupervisorManagementController extends Controller
{
    protected function ensureAdmin(): void
    {
        if (Auth::check() && Auth::user()->employee && Auth::user()->employee->role === 'line_supervisor') {
            abort(403, 'Only admin can manage supervisors here. Use your dashboard to change your own password.');
        }
    }

    public function index()
    {
        $this->ensureAdmin();
        $supervisorIds = ProductionSection::whereNotNull('supervisor_id')->pluck('supervisor_id')->unique();
        $employees = Employee::with('user')->whereIn('id', $supervisorIds)->orderBy('name')->get();

        $supervisors = $employees->map(function (Employee $emp) {
            $sections = ProductionSection::where('supervisor_id', $emp->id)->pluck('name')->implode(', ');
            return [
                'employee' => $emp,
                'sections' => $sections,
                'has_login' => (bool) $emp->user_id,
            ];
        });

        return view('erp.supervisors.index', compact('supervisors'));
    }

    public function updatePassword(Request $request, Employee $employee)
    {
        $this->ensureAdmin();
        $isSupervisor = ProductionSection::where('supervisor_id', $employee->id)->exists();
        if (!$isSupervisor) {
            abort(403, 'This employee is not a section supervisor.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $employee->user_id ? User::find($employee->user_id) : null;
        if (!$user && $employee->email) {
            $user = User::where('email', $employee->email)->first();
            if ($user) {
                $employee->update(['user_id' => $user->id]);
            }
        }
        if (!$user && $employee->email) {
            $user = User::create([
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => Hash::make($request->password),
            ]);
            $employee->update(['user_id' => $user->id]);
        }
        if (!$user) {
            return back()->with('error', 'This supervisor has no email on their employee record. Add email in Employees first, then set password here.');
        }

        $user->update(['password' => Hash::make($request->password)]);
        $employee->update(['role' => 'line_supervisor']);
        return back()->with('success', 'Password updated for ' . $employee->name . '. They can log in at Supervisor Login with this password.');
    }
}

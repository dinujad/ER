<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login', ['supervisorLogin' => false]);
    }

    public function showSupervisorLoginForm()
    {
        return view('auth.login', ['supervisorLogin' => true]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Flush any stale auth before attempting so a broken old session never blocks login
        if (Auth::check()) {
            Auth::logout();
        }

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->role === 'sales_person') {
                return redirect()->intended(route('sales-person.dashboard'));
            }
            if ($user->role === 'driver') {
                return redirect()->intended(route('driver.dashboard'));
            }
            if ($user->employee && $user->employee->role === 'line_supervisor') {
                return redirect()->route('supervisor.dashboard');
            }

            // Permission-based users: redirect to first accessible page
            if ($user->role_id && !$user->isSystemAdmin()) {
                $request->session()->forget('url.intended');
                return redirect()->to($this->firstAccessibleUrl($user));
            }

            $request->session()->forget('url.intended');
            return redirect()->to('/erp/dashboard');
        }

        return back()
            ->withErrors(['email' => 'Email or password is incorrect. Please try again.'])
            ->onlyInput('email');
    }

    public function supervisorLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->employee && $user->employee->role === 'line_supervisor') {
                $section = \App\Models\ProductionSection::where('supervisor_id', $user->employee->id)->first();
                if ($section) {
                    return redirect()->intended(route('supervisor.dashboard'));
                }
            }
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors([
                'email' => 'Only section supervisors can login here. You are not assigned to a section.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Email or password is incorrect. Use the email on your employee record. Ask admin to set or reset your password from Employees → Supervisors.',
        ])->onlyInput('email');
    }

    /**
     * Return the first ERP URL the user is allowed to see, in priority order.
     * Falls back to /erp/dashboard (which will show a 403 if truly no access).
     */
    protected function firstAccessibleUrl($user): string
    {
        $map = [
            'dashboard.view'       => '/erp/dashboard',
            'orders.view'          => '/erp/orders',
            'inventory.view'       => '/erp/inventory',
            'purchases.view'       => '/erp/purchases',
            'products.view'        => '/erp/products',
            'customers.view'       => '/erp/customers',
            'pos.view'             => '/erp/pos',
            'manufacturing.view'   => '/erp/manufacturing',
            'employees.view'       => '/erp/employees',
            'hrm.view'             => '/erp/hrm',
            'expenses.view'        => '/erp/expenses',
            'reports.view'         => '/erp/reports',
            'vehicles.view'        => '/erp/vehicles',
            'sales_persons.view'   => '/erp/sales-persons',
            'users.view'           => '/erp/users',
            'roles.view'           => '/erp/roles',
        ];

        foreach ($map as $permission => $url) {
            if ($user->can_access($permission)) {
                return $url;
            }
        }

        return '/erp/dashboard';
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('url.intended', null);
        return redirect('/login');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically enforces permission checks based on the current route name.
 * System admins (isSystemAdmin()) bypass all checks.
 * Users with a role_id must have the matching permission to access the route.
 */
class EnforceRoutePermission
{
    /**
     * Route name pattern → required permission key.
     * Patterns ending with .* match any route starting with that prefix.
     */
    protected array $routePermissions = [
        'erp.dashboard'                  => 'dashboard.view',

        // POS
        'pos.*'                          => 'pos.view',

        // Orders (exact names beat orders.*)
        'orders.create'                  => 'orders.create',
        'orders.store'                   => 'orders.create',
        'orders.edit'                    => 'orders.edit',
        'orders.update'                  => 'orders.edit',
        'orders.destroy'                 => 'orders.delete',
        'orders.*'                       => 'orders.view',
        'payment-management.*'           => 'orders.view',
        'sale-returns.*'                 => 'orders.view',
        'retail-sale-returns.*'          => 'orders.view',
        'retail-quotations.*'            => 'pos.view',
        'order-payments.*'               => 'orders.payment',

        // Sales persons & routes
        'sales-persons.*'                => 'sales_persons.view',
        'sales-routes.*'                 => 'sales_persons.view',
        'commissions.*'                  => 'sales_persons.view',

        // Inventory
        'inventory.*'                    => 'inventory.view',

        // Products, categories, brands, units
        // Used by admin order create — allow with orders.view (exact match beats products.*)
        'products.get_variations'        => 'orders.view',
        'products.*'                     => 'products.view',
        'categories.*'                   => 'products.view',
        'brands.*'                       => 'products.view',
        'units.*'                        => 'products.view',
        'price-groups.*'                 => 'products.view',
        'barcode.*'                      => 'products.view',

        // Purchases & suppliers
        'purchases.*'                    => 'purchases.view',
        'suppliers.*'                    => 'purchases.view',
        'purchase-payments.*'            => 'purchases.view',
        'purchase-payment-management.*'  => 'purchases.view',

        // Customers
        'erp.customers.*'                => 'customers.view',

        // Sales persons
        'erp.sales-persons.*'            => 'sales_persons.view',
        'sales-commissions.*'            => 'sales_persons.view',

        // Manufacturing (exact names beat jobs.* / manufacturing.*)
        'jobs.costs'                     => 'manufacturing.costs',
        'jobs.costs.store'               => 'manufacturing.costs',
        'jobs.costs.destroy'             => 'manufacturing.costs',
        'cost-analyst.*'                 => 'manufacturing.costs',
        'manufacturing.*'                => 'manufacturing.view',
        'jobs.*'                         => 'manufacturing.view',
        'sections.*'                     => 'manufacturing.view',
        'lines.*'                        => 'manufacturing.view',
        'hourly-production.*'            => 'manufacturing.production',
        'manufacturing.washing-stock.*'  => 'manufacturing.view',
        'manufacturing.washing.*'        => 'manufacturing.washing',
        'manufacturing.qc.*'             => 'manufacturing.qc',
        'manufacturing.cutting.*'        => 'manufacturing.cutting',
        'manufacturing.final.*'          => 'manufacturing.final',

        // Reports
        'reports.*'                      => 'reports.view',

        // Employees
        'employees.hik-connect-import.*' => 'employees.create',
        'employees.import.create'        => 'employees.create',
        'employees.import.store'         => 'employees.create',
        'employees.create'               => 'employees.create',
        'employees.store'                => 'employees.create',
        'employees.edit'                 => 'employees.edit',
        'employees.update'               => 'employees.edit',
        'employees.destroy'              => 'employees.delete',
        'employees.bulk-delete'          => 'employees.delete',
        'employees.*'                    => 'employees.view',
        'supervisors.*'                  => 'employees.view',

        // HRM
        'hrm.*'                          => 'hrm.view',
        'attendance.*'                   => 'hrm.attendance',
        'leave-types.*'                  => 'hrm.leave',
        'leave-requests.*'               => 'hrm.leave',
        'ot-requests.*'                  => 'hrm.attendance',
        'hr.*'                           => 'hrm.view',
        'payroll-runs.*'                 => 'hrm.payroll',
        'salary-components.*'            => 'hrm.payroll',
        'employee-salaries.*'            => 'hrm.payroll',
        'departments.*'                  => 'hrm.view',
        'designations.*'                 => 'hrm.view',
        'shifts.*'                       => 'hrm.view',
        'roster.*'                       => 'hrm.view',
        'announcements.*'                => 'hrm.view',

        // Expenses
        'expenses.*'                     => 'expenses.view',
        'field-expenses.*'               => 'expenses.view',
        'expense-categories.*'           => 'expenses.view',

        // Vehicles
        'vehicles.*'                     => 'vehicles.view',
        'drivers.*'                      => 'vehicles.view',
        'vehicle-runs.*'                 => 'vehicles.view',

        // Settings
        'erp.settings.*'                 => 'cheque.settings',
        'cheque-notification-settings.*' => 'cheque.settings',

        // Users & Roles
        'erp.users.*'                    => 'users.view',
        'roles.*'                        => 'roles.view',

        // Accounting
        'accounting.*'                   => 'accounting.view',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // System admins bypass all checks
        if ($user->isSystemAdmin()) {
            return $next($request);
        }

        // Users without any role_id get no ERP access
        if (!$user->role_id) {
            abort(403, 'No role assigned. Please contact the administrator.');
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return $next($request);
        }

        $required = \App\Support\ActionPermissions::required($request->route(), $request->method())
            ?? $this->resolveRequiredPermission($routeName);

        if ($required && !$user->can_access($required)) {
            abort(403, "You don't have permission to access this area. Required: {$required}");
        }

        return $next($request);
    }

    protected function resolveRequiredPermission(string $routeName): ?string
    {
        // Exact match first
        if (isset($this->routePermissions[$routeName])) {
            return $this->routePermissions[$routeName];
        }

        // Wildcard prefix match (e.g. "inventory.*" matches "inventory.index", "inventory.create", etc.)
        foreach ($this->routePermissions as $pattern => $permission) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($routeName, $prefix . '.') || $routeName === $prefix) {
                    return $permission;
                }
            }
        }

        return null; // no restriction for this route
    }
}

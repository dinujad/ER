<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Routes;

/** Explicit edit/delete grants, independent of a module's view/create access. */
class ActionPermissions
{
    private const MODULES = [
        'erp.users' => 'users', 'erp.customers' => 'customers',
        'erp.sales-persons' => 'sales_persons',
        'erp.settings' => 'cheque', 'cheque-notification-settings' => 'cheque',
        'sales-person.expenses' => 'expenses', 'driver.delete-item' => 'vehicles',
        'driver.jobs' => 'vehicles', 'supervisor.employees' => 'employees',
        'products' => 'products', 'categories' => 'products', 'brands' => 'products',
        'units' => 'products', 'price-groups' => 'products', 'barcode' => 'products',
        'inventory' => 'inventory', 'stock-adjustment' => 'inventory',
        'orders' => 'orders', 'order-payments' => 'orders',
        'purchases' => 'purchases', 'suppliers' => 'purchases', 'purchase-payments' => 'purchases',
        'sales-persons' => 'sales_persons', 'sales-routes' => 'sales_persons',
        'commissions' => 'sales_persons', 'sales-commissions' => 'sales_persons',
        'jobs' => 'manufacturing', 'manufacturing' => 'manufacturing',
        'sections' => 'manufacturing', 'lines' => 'manufacturing', 'product-recipes' => 'manufacturing',
        'hourly-production' => 'manufacturing',
        'employees' => 'employees', 'supervisors' => 'employees',
        'hr' => 'hrm', 'hrm' => 'hrm', 'attendance' => 'hrm',
        'departments' => 'hrm', 'designations' => 'hrm', 'shifts' => 'hrm',
        'leave-types' => 'hrm', 'leave-requests' => 'hrm', 'ot-requests' => 'hrm',
        'salary-components' => 'hrm', 'employee-salaries' => 'hrm', 'payroll-runs' => 'hrm',
        'roster' => 'hrm', 'announcements' => 'hrm',
        'expenses' => 'expenses', 'expense-categories' => 'expenses', 'field-expenses' => 'expenses',
        'vehicles' => 'vehicles', 'drivers' => 'vehicles', 'vehicle-runs' => 'vehicles',
        'essentials' => 'essentials', 'accounting' => 'accounting', 'media' => 'accounting',
    ];

    private const EDIT_ROUTES = [
        'attendance.manual', 'attendance.manual.store', 'stock-adjustment.create', 'stock-adjustment.store',
        'sales-persons.assign-route', 'sales-persons.reassign', 'sales-persons.resend',
    ];

    public static function action(Route $route, ?string $method = null): ?string
    {
        $name = $route->getName() ?? '';
        // Personal password changes and an unsaved POS cart keep their existing access rules.
        if ($name === 'supervisor.password.update' || str_starts_with($name, 'pos.cart.')
            || in_array($name, ['hr.reviews.acknowledge', 'hr.letters.acknowledge'], true)) {
            return null;
        }

        $method ??= $route->methods()[0];
        $controllerMethod = $route->getActionMethod();
        if ($method === 'DELETE'
            || preg_match('/(^|[.\-_])(destroy|delete|remove|clear-all)([.\-_]|$)/', $name)
            || preg_match('/^(destroy|delete|remove)/i', $controllerMethod)) {
            return 'delete';
        }
        if (in_array($name, self::EDIT_ROUTES, true)
            || in_array($method, ['PUT', 'PATCH'], true)
            || preg_match('/(^|[.\-_])(edit|update)([.\-_]|$)/', $name)
            || preg_match('/^(edit|update)/i', $controllerMethod)) {
            return 'edit';
        }

        return null;
    }

    public static function required(Route $route, ?string $method = null): ?string
    {
        $action = self::action($route, $method);
        if (!$action) {
            return null;
        }
        $name = $route->getName() ?? '';
        if (str_starts_with($route->uri(), 'accounting/')) {
            return 'accounting.' . $action;
        }
        foreach (self::MODULES as $prefix => $module) {
            if ($name === $prefix || str_starts_with($name, $prefix . '.')) {
                return $module . '.' . $action;
            }
        }

        // Unmapped edit/delete routes are administrator-only, never unrestricted.
        return 'admin.only';
    }

    public static function adminOnly(Route $route): bool
    {
        $name = $route->getName() ?? '';

        // Only administrators can grant role permissions.
        return (str_starts_with($name, 'roles.') && $name !== 'roles.index')
            || str_starts_with($route->uri(), 'accounting/install');
    }

    public static function allows(?User $user, Route $route, ?string $method = null): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isSystemAdmin()) {
            return true;
        }
        if (self::adminOnly($route)) {
            return false;
        }
        $permission = self::required($route, $method);

        return $permission === null || ($permission !== 'admin.only' && $user->can_access($permission));
    }

    public static function allowsRoute(?User $user, string $name): bool
    {
        $route = Routes::getRoutes()->getByName($name);

        return $route && self::allows($user, $route);
    }
}

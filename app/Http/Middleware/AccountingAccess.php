<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isSystemAdmin()) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        $isWritePath = preg_match(
            '#(^|/)(install|uninstall|update|create|edit|destroy|reverse|store|transfers|reconcile|settings)(/|$)#',
            $path
        ) === 1;
        $permission = (!$request->isMethodSafe() || $isWritePath)
            ? 'accounting.manage'
            : 'accounting.view';
        $permission = \App\Support\ActionPermissions::required($request->route(), $request->method()) ?? $permission;

        if (!$user->can_access($permission)) {
            abort(403, "You don't have permission to access this accounting action.");
        }

        return $next($request);
    }
}

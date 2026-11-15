<?php

namespace App\Http\Middleware;

use App\Support\ActionPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceActionPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if ($route && (ActionPermissions::adminOnly($route)
            || ActionPermissions::required($route, $request->method()) !== null)) {
            if (!$request->user()) {
                return redirect()->route('login');
            }
            abort_unless(ActionPermissions::allows($request->user(), $route, $request->method()), 403,
                'Only an administrator or a role with explicit permission can edit or delete these records.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // System admin bypasses all permission checks
        if ($user->isSystemAdmin()) {
            return $next($request);
        }

        // Check each required permission (user must have ALL)
        foreach ($permissions as $permission) {
            if (!$user->can_access($permission)) {
                abort(403, "You don't have permission to access this page.");
            }
        }

        return $next($request);
    }
}

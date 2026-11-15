<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ultimate POS accounting code expects session('business.id') and session('user.business_id').
 */
class AccountingSessionDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('business.id')) {
            session(['business.id' => 1]);
        }
        if (!session()->has('user.business_id')) {
            session(['user.business_id' => 1]);
        }

        return $next($request);
    }
}

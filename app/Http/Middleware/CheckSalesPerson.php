<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSalesPerson
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('sales-person.login');
        }

        $user = auth()->user();

        if ($user->role !== 'sales_person') {
            if ($user->role === 'admin') {
                return redirect()->to('/erp/dashboard');
            }

            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}

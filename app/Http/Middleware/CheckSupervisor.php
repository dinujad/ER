<?php

namespace App\Http\Middleware;

use App\Models\ProductionSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('supervisor.login');
        }

        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee || $employee->role !== 'line_supervisor') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('supervisor.login')
                ->withErrors(['email' => 'Only supervisors can access this area.']);
        }

        $section = ProductionSection::where('supervisor_id', $employee->id)->first();
        if (!$section) {
            return redirect()->route('supervisor.login')
                ->withErrors(['email' => 'You are not assigned to any section.']);
        }

        return $next($request);
    }
}

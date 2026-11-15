<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\FinancialDashboardService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FinancialDashboardController extends Controller
{
    public function show(Request $request, FinancialDashboardService $service)
    {
        $start = $request->input('start_date', date('Y-01-01'));
        $end = $request->input('end_date', date('Y-m-d'));
        $dashboard = $service->dashboard($start, $end);

        return view('accounting::phase4.dashboard.show', [
            'dashboard' => $dashboard,
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\FinancialKpiAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FinancialKpiController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $kpis = FinancialKpiAnalyticsService::analyze($from, $to);

        return view('accounting::phase4.kpi.index', compact('kpis', 'from', 'to'));
    }
}
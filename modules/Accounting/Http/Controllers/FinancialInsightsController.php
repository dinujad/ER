<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\FinancialInsightsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class FinancialInsightsController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-d'));
        $insights = Schema::hasTable('acct_financial_insights')
            ? DB::table('acct_financial_insights')
                ->where('business_id', $this->sessionBusinessId())
                ->orderByDesc('id')
                ->limit(50)
                ->get()
            : collect();

        return view('accounting::phase5.insights.index', [
            'pageTitle' => 'Financial Insights',
            'pageSub' => 'Deterministic metrics · LKR',
            'insights' => $insights,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function generate(Request $request, FinancialInsightsService $service)
    {
        $this->requireManage();
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-d'));
        $service->generate($this->sessionBusinessId(), $from, $to);

        return redirect('accounting/phase5/insights')->with('status', 'Insights generated from GL.');
    }
}

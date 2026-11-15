<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\CashFlowForecastService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class CashForecastController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $scenarios = Schema::hasTable('acct_cash_forecast_scenarios')
            ? DB::table('acct_cash_forecast_scenarios')->orderByDesc('id')->paginate(20)
            : collect();

        return view('accounting::phase4.forecast.index', compact('scenarios'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'scenario_type' => 'required|in:base,optimistic,conservative',
            'horizon_days' => 'required|integer|min:7|max:365',
            'collection_pct' => 'nullable|numeric|min:0|max:100',
            'delay_days' => 'nullable|integer|min:0|max:90',
        ]);

        $id = DB::table('acct_cash_forecast_scenarios')->insertGetId([
            'business_id' => (int) (session('business.id') ?? 0),
            'name' => $data['name'],
            'scenario_type' => $data['scenario_type'],
            'assumptions' => json_encode([
                'collection_pct' => $data['collection_pct'] ?? 100,
                'delay_days' => $data['delay_days'] ?? 0,
            ]),
            'horizon_days' => $data['horizon_days'],
            'status' => 'active',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $run = CashFlowForecastService::runScenario($id, date('Y-m-d'), Auth::id());

        return view('accounting::phase4.forecast.result', compact('run', 'id'));
    }
}
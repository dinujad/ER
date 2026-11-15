<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\FaAsset;
use App\Models\Accounting\FaDepreciationRun;
use App\Models\Accounting\FaDepreciationSchedule;
use App\Services\Accounting\DepreciationEngine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class DepreciationController extends Controller
{
    use RequiresAccountingPermission;

    public function runs()
    {
        $runs = Schema::hasTable('fa_depreciation_runs')
            ? FaDepreciationRun::query()->orderByDesc('period_year')->orderByDesc('period_month')->paginate(25)
            : new LengthAwarePaginator([], 0, 25);

        return view('accounting::phase3.depreciation.runs', compact('runs'));
    }

    public function assetSchedule($assetId)
    {
        $asset = FaAsset::with('depreciationSchedules')->findOrFail($assetId);

        return view('accounting::phase3.depreciation.asset_schedule', compact('asset'));
    }

    public function preview(Request $request)
    {
        $year = (int) $request->get('year', (int) date('Y'));
        $month = (int) $request->get('month', (int) date('n'));
        $runDate = $request->get('run_date', date('Y-m-t', strtotime("$year-$month-01")));

        $lines = [];
        $total = 0.0;
        if (Schema::hasTable('fa_depreciation_schedules')) {
            $schedules = FaDepreciationSchedule::query()
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('status', 'scheduled')
                ->whereHas('asset', fn ($q) => $q->where('status', 'active'))
                ->with('asset')
                ->get();
            foreach ($schedules as $s) {
                $amt = (float) $s->scheduled_amount;
                $total = round($total + $amt, 2);
                $lines[] = [
                    'asset' => $s->asset,
                    'amount' => $amt,
                ];
            }
        }

        return view('accounting::phase3.depreciation.preview', compact('year', 'month', 'runDate', 'lines', 'total'));
    }

    public function postRun(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'run_date' => 'required|date',
        ]);

        try {
            DepreciationEngine::runPeriod(0, (int) $data['year'], (int) $data['month'], $data['run_date'], Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/depreciation/runs');
    }

    public function reverseRun(Request $request, $id)
    {
        $this->requireManage();
        $run = FaDepreciationRun::findOrFail($id);

        try {
            DepreciationEngine::reverseRun($run, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/depreciation/runs');
    }
}
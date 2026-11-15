<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AcctCostCenter;
use App\Services\Accounting\CostCenterReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\AccountingExport;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class CostCenterReportController extends Controller
{
    use RequiresAccountingPermission;

    public function profitAndLoss(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $ccParam = $request->get('cost_center_id', '');

        $costCenterId = $ccParam === 'unassigned' || $ccParam === '' ? null : (int) $ccParam;
        if ($ccParam !== '' && $ccParam !== 'unassigned' && $costCenterId <= 0) {
            $costCenterId = null;
        }

        $filterCc = null;
        if ($ccParam === 'unassigned' || $ccParam === '') {
            $filterCc = null;
        } elseif (ctype_digit((string) $ccParam)) {
            $filterCc = (int) $ccParam;
        }
        $report = CostCenterReportService::profitAndLoss($filterCc, $from, $to);
        $centers = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->orderBy('code')->get()
            : collect();

        return view('accounting::phase3.cost_center_reports.pl', compact('report', 'centers', 'from', 'to', 'ccParam'));
    }

    public function profitAndLossExport(Request $request, string $format)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $ccParam = $request->get('cost_center_id', '');
        $costCenterId = $ccParam === 'unassigned' ? null : ($ccParam !== '' ? (int) $ccParam : null);
        $report = CostCenterReportService::profitAndLoss($costCenterId, $from, $to);
        $data = compact('report', 'from', 'to', 'ccParam');
        $view = view('accounting::phase3.reports.cost_center_pl', $data);

        if ($format === 'pdf') {
            return Pdf::loadView('accounting::phase3.reports.cost_center_pl', $data)->setPaper('a4')->download('cost-center-pl.pdf');
        }

        return Excel::download(new AccountingExport($view), 'cost-center-pl.'.$format);
    }

    public function journals(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $ccParam = $request->get('cost_center_id', 'unassigned');
        $q = DB::table('journal_entries')->where('reversed', 0)->whereBetween('date', [$from, $to])->orderByDesc('date');
        if ($ccParam === 'unassigned') {
            $q->whereNull('cost_center_id');
        } elseif ($ccParam !== '') {
            $q->where('cost_center_id', (int) $ccParam);
        }

        $entries = Schema::hasTable('journal_entries') ? $q->paginate(50)->withQueryString() : collect();

        return view('accounting::phase3.cost_center_reports.journals', compact('entries', 'from', 'to', 'ccParam'));
    }
}
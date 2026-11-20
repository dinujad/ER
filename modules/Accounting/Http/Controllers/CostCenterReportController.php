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
        $ccParam = (string) $request->get('cost_center_id', 'all');

        [$report, $scopeLabel, $scopeMode] = $this->resolveReport($ccParam, $from, $to);

        $centers = Schema::hasTable('acct_cost_centers')
            ? AcctCostCenter::query()->orderBy('code')->get()
            : collect();

        $income = (float) ($report['income'] ?? 0);
        $expense = (float) ($report['expense'] ?? 0);
        $net = (float) ($report['net'] ?? 0);
        $margin = $income > 0.0001 ? round(($net / $income) * 100, 2) : null;
        $hasLines = ! empty($report['lines']);
        $hasCostCenterColumn = Schema::hasColumn('journal_entries', 'cost_center_id');

        return view('accounting::phase3.cost_center_reports.pl', compact(
            'report',
            'centers',
            'from',
            'to',
            'ccParam',
            'scopeLabel',
            'scopeMode',
            'income',
            'expense',
            'net',
            'margin',
            'hasLines',
            'hasCostCenterColumn'
        ));
    }

    public function profitAndLossExport(Request $request, string $format)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $ccParam = (string) $request->get('cost_center_id', 'all');
        [$report] = $this->resolveReport($ccParam, $from, $to);
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
        $ccParam = (string) $request->get('cost_center_id', 'unassigned');
        $q = DB::table('journal_entries')->where('reversed', 0)->whereBetween('date', [$from, $to])->orderByDesc('date');

        if ($ccParam === 'unassigned') {
            $q->whereNull('cost_center_id');
        } elseif ($ccParam !== '' && $ccParam !== 'all' && ctype_digit($ccParam)) {
            $q->where('cost_center_id', (int) $ccParam);
        }

        $entries = Schema::hasTable('journal_entries') ? $q->paginate(50)->withQueryString() : collect();

        return view('accounting::phase3.cost_center_reports.journals', compact('entries', 'from', 'to', 'ccParam'));
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string}
     */
    private function resolveReport(string $ccParam, string $from, string $to): array
    {
        if ($ccParam === 'all' || $ccParam === '') {
            return [
                CostCenterReportService::companyWideProfitAndLoss($from, $to),
                'All cost centers (company-wide)',
                'all',
            ];
        }

        if ($ccParam === 'unassigned') {
            return [
                CostCenterReportService::profitAndLoss(null, $from, $to),
                'Unassigned journals only',
                'unassigned',
            ];
        }

        $id = (int) $ccParam;
        $name = Schema::hasTable('acct_cost_centers')
            ? (string) (AcctCostCenter::query()->where('id', $id)->value('name') ?? ('#'.$id))
            : '#'.$id;

        return [
            CostCenterReportService::profitAndLoss($id, $from, $to),
            'Cost center: '.$name,
            'center',
        ];
    }
}

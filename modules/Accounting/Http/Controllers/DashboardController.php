<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\FinancialDashboardService;
use App\Services\AccountingWorkspaceMetrics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;

class DashboardController extends Controller
{
    /**
     * Accounting Workspace overview — KPIs from posted journals (LKR).
     */
    public function index(Request $request, FinancialDashboardService $financialDashboard)
    {
        $start = $request->input('start_date') ?: (function_exists('financial_year_start_date') ? financial_year_start_date() : date('Y-01-01'));
        $end = $request->input('end_date') ?: date('Y-m-d');

        $summary = $financialDashboard->dashboard($start, $end);

        $counts = [
            'accounts' => Schema::hasTable('chart_of_accounts')
                ? ChartOfAccount::query()->where('active', 1)->count()
                : 0,
            'journal_lines' => Schema::hasTable('journal_entries')
                ? JournalEntry::query()->where(function ($q) {
                    $q->where('reversed', 0)->orWhereNull('reversed');
                })->count()
                : 0,
        ];

        return view('accounting::dashboard.index', [
            'summary' => $summary,
            'counts' => $counts,
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    /**
     * Legacy AJAX totals (kept for bookmarks / old scripts).
     */
    public function get_totals()
    {
        if (! request()->ajax()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $output = [
            'no_journal_entries' => Schema::hasTable('journal_entries')
                ? JournalEntry::query()->count()
                : 0,
            'no_charts_of_account' => Schema::hasTable('chart_of_accounts')
                ? ChartOfAccount::query()->where('active', 1)->count()
                : 0,
            'all_transactions' => 0,
        ];

        return response()->json($output);
    }
}

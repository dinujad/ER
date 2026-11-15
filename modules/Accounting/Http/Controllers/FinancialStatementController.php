<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\FinancialStatementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FinancialStatementController extends Controller
{
    public function profitAndLoss(Request $request)
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::profitAndLoss(
            $from,
            $to,
            $request->input('compare_from'),
            $request->input('compare_to')
        );

        return view('accounting::phase4.statements.profit_and_loss', compact('report', 'from', 'to'));
    }

    public function balanceSheet(Request $request)
    {
        $asOf = $request->input('as_of', date('Y-m-d'));
        $report = FinancialStatementService::balanceSheet($asOf);

        return view('accounting::phase4.statements.balance_sheet', compact('report', 'asOf'));
    }

    public function cashFlow(Request $request)
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::cashFlowIndirect($from, $to);

        return view('accounting::phase4.statements.cash_flow', compact('report', 'from', 'to'));
    }

    public function changesInEquity(Request $request)
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::changesInEquity($from, $to);

        return view('accounting::phase4.statements.changes_in_equity', compact('report', 'from', 'to'));
    }

    public function trialBalance(Request $request)
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::trialBalance($from, $to);

        return view('accounting::phase4.statements.trial_balance', compact('report', 'from', 'to'));
    }
}
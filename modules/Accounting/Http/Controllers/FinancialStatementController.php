<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\FinancialStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\AccountingExport;

class FinancialStatementController extends Controller
{
    public function profitAndLoss(Request $request)
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $compareFrom = $request->input('compare_from');
        $compareTo = $request->input('compare_to');
        $report = FinancialStatementService::profitAndLoss($from, $to, $compareFrom, $compareTo);

        return view('accounting::phase4.statements.profit_and_loss', compact('report', 'from', 'to', 'compareFrom', 'compareTo'));
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

    public function export(Request $request, string $statement, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xlsx', 'xls', 'csv'], true), 404);
        abort_unless(in_array($statement, [
            'profit-and-loss', 'balance-sheet', 'cash-flow', 'changes-in-equity', 'trial-balance',
        ], true), 404);

        $payload = $this->exportPayload($request, $statement);
        $view = view('accounting::phase4.statements._export', $payload);

        if ($format === 'pdf') {
            return Pdf::loadHTML($view->render())->setPaper('a4')->download($payload['filename'].'.pdf');
        }

        return Excel::download(new AccountingExport($view), $payload['filename'].'.'.$format);
    }

    /**
     * @return array<string, mixed>
     */
    private function exportPayload(Request $request, string $statement): array
    {
        return match ($statement) {
            'profit-and-loss' => $this->plExport($request),
            'balance-sheet' => $this->bsExport($request),
            'cash-flow' => $this->cfExport($request),
            'changes-in-equity' => $this->equityExport($request),
            'trial-balance' => $this->tbExport($request),
        };
    }

    /** @return array<string, mixed> */
    private function plExport(Request $request): array
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::profitAndLoss($from, $to, $request->input('compare_from'), $request->input('compare_to'));
        $income = (float) $report['income'];
        $expense = (float) $report['expense'];
        $net = (float) $report['net'];

        $rows = [];
        $rows[] = ['cells' => ['', 'Income', '', ''], '_class' => 'section'];
        foreach ($report['lines'] as $line) {
            if (($line['account_type'] ?? '') !== 'income') {
                continue;
            }
            $rows[] = ['cells' => [$line['gl_code'], $line['name'], $line['account_type'], (float) $line['amount']]];
        }
        $rows[] = ['cells' => ['', 'Total Income', '', $income], '_class' => 'total'];
        $rows[] = ['cells' => ['', 'Expenses', '', ''], '_class' => 'section'];
        foreach ($report['lines'] as $line) {
            if (($line['account_type'] ?? '') !== 'expense') {
                continue;
            }
            $rows[] = ['cells' => [$line['gl_code'], $line['name'], $line['account_type'], (float) $line['amount']]];
        }
        $rows[] = ['cells' => ['', 'Total Expenses', '', $expense], '_class' => 'total'];
        $rows[] = ['cells' => ['', 'Net Profit / (Loss)', '', $net], '_class' => 'total'];

        return [
            'title' => 'Profit and Loss',
            'subtitle' => $from.' → '.$to,
            'filename' => 'profit-and-loss-'.$from.'-'.$to,
            'columns' => ['GL', 'Account', 'Type', 'Amount'],
            'rows' => $rows,
            'kpis' => [
                ['label' => 'Income', 'value' => 'LKR '.number_format($income, 2)],
                ['label' => 'Expenses', 'value' => 'LKR '.number_format($expense, 2)],
                ['label' => 'Net', 'value' => 'LKR '.number_format($net, 2)],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function bsExport(Request $request): array
    {
        $asOf = $request->input('as_of', date('Y-m-d'));
        $report = FinancialStatementService::balanceSheet($asOf);
        $rows = [];
        foreach (['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity'] as $key => $label) {
            $rows[] = ['cells' => ['', $label, ''], '_class' => 'section'];
            foreach ($report['sections'][$key] ?? [] as $line) {
                $rows[] = ['cells' => [$line['gl_code'], $line['name'], (float) $line['balance']]];
            }
            $rows[] = ['cells' => ['', 'Total '.$label, (float) ($report['totals'][$key] ?? 0)], '_class' => 'total'];
        }
        $rows[] = ['cells' => ['', 'Current-period earnings (in equity)', (float) $report['current_earnings']], '_class' => 'section'];

        return [
            'title' => 'Balance Sheet',
            'subtitle' => 'As of '.$asOf,
            'filename' => 'balance-sheet-'.$asOf,
            'columns' => ['GL', 'Account', 'Balance'],
            'rows' => $rows,
            'kpis' => [
                ['label' => 'Assets', 'value' => 'LKR '.number_format((float) $report['assets'], 2)],
                ['label' => 'Liab + Equity', 'value' => 'LKR '.number_format((float) $report['liabilities_plus_equity'], 2)],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function cfExport(Request $request): array
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::cashFlowIndirect($from, $to);
        $rows = [];
        foreach ($report['buckets'] as $bucket => $amount) {
            $rows[] = ['cells' => [ucfirst($bucket), (float) $amount], '_class' => 'total'];
        }
        foreach ($report['lines'] as $line) {
            $rows[] = ['cells' => [
                ($line['gl_code'] ?? '').' '.$line['name'].' ['.$line['bucket'].']',
                (float) $line['cash_effect'],
            ]];
        }

        return [
            'title' => 'Cash Flow (Indirect)',
            'subtitle' => $from.' → '.$to,
            'filename' => 'cash-flow-'.$from.'-'.$to,
            'columns' => ['Line', 'Amount'],
            'rows' => $rows,
            'kpis' => [
                ['label' => 'Net change', 'value' => 'LKR '.number_format((float) $report['net_change'], 2)],
                ['label' => 'Cash GL change', 'value' => 'LKR '.number_format((float) ($report['meta']['cash_gl_change'] ?? 0), 2)],
            ],
            'notes' => $report['meta']['reconciliation_note'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function equityExport(Request $request): array
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::changesInEquity($from, $to);
        $rows = [];
        foreach ($report['lines'] as $line) {
            $rows[] = ['cells' => [
                $line['gl_code'],
                $line['name'],
                (float) $line['opening'],
                (float) $line['movement'],
                (float) $line['closing'],
            ]];
        }
        $rows[] = ['cells' => ['', 'Current-period earnings', '', (float) $report['current_period_earnings'], ''], '_class' => 'section'];
        $rows[] = ['cells' => ['', 'Closing equity (incl. earnings)', '', '', (float) $report['closing_equity']], '_class' => 'total'];

        return [
            'title' => 'Changes in Equity',
            'subtitle' => $from.' → '.$to,
            'filename' => 'changes-in-equity-'.$from.'-'.$to,
            'columns' => ['GL', 'Account', 'Opening', 'Movement', 'Closing'],
            'rows' => $rows,
            'kpis' => [
                ['label' => 'Opening', 'value' => 'LKR '.number_format((float) $report['opening_equity'], 2)],
                ['label' => 'Closing', 'value' => 'LKR '.number_format((float) $report['closing_equity'], 2)],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function tbExport(Request $request): array
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        $report = FinancialStatementService::trialBalance($from, $to);
        $rows = [];
        foreach ($report['rows'] as $line) {
            $rows[] = ['cells' => [
                $line['gl_code'],
                $line['name'],
                $line['account_type'],
                (float) $line['debit'],
                (float) $line['credit'],
            ]];
        }
        $rows[] = ['cells' => ['', 'Totals', '', (float) $report['total_debit'], (float) $report['total_credit']], '_class' => 'total'];

        return [
            'title' => 'Trial Balance',
            'subtitle' => $from.' → '.$to,
            'filename' => 'trial-balance-'.$from.'-'.$to,
            'columns' => ['GL', 'Account', 'Type', 'Debit', 'Credit'],
            'rows' => $rows,
            'kpis' => [
                ['label' => 'Total Debit', 'value' => 'LKR '.number_format((float) $report['total_debit'], 2)],
                ['label' => 'Total Credit', 'value' => 'LKR '.number_format((float) $report['total_credit'], 2)],
            ],
        ];
    }
}

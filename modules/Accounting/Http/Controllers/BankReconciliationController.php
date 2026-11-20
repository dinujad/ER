<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\BankStatementLine;
use App\Services\Accounting\BankMatchingService;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\BankStatementImportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 7 — Xero-style reconcile screen: import with saved mapping, statement vs ledger balance,
 * queue of unmatched bank lines, match one line to many transactions, net settlements with fees.
 */
class BankReconciliationController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        $accounts = $this->bankAccounts();
        $accountId = (int) ($request->get('account_id') ?: ($accounts->firstWhere('gl_code', '1010')->id ?? $accounts->first()->id ?? 0));
        $account = $accounts->firstWhere('id', $accountId);
        $ws = $accountId ? BankReconciliationService::workspace($accountId) : null;
        $mapping = $accountId ? BankReconciliationService::mappingFor($accountId) : null;

        $selected = null;
        $candidates = collect();
        if ($request->filled('line')) {
            $selected = BankStatementLine::query()->with('import')->find((int) $request->get('line'));
            if ($selected && (int) $selected->import?->chart_of_account_id === $accountId && $selected->match_status !== 'matched') {
                $candidates = BankReconciliationService::candidates($selected, (int) $request->get('days', 30), $request->get('q'));
            } else {
                $selected = null;
            }
        }

        $bankAccount = $accountId ? \App\Services\Accounting\BankAccountService::forLedger($accountId) : null;
        $postAccounts = DB::table('chart_of_accounts')->where('active', 1)->where('id', '!=', $accountId)->orderBy('gl_code')->get(['id', 'gl_code', 'name', 'account_type']);
        $transferTargets = \Illuminate\Support\Facades\Schema::hasTable('bank_accounts')
            ? DB::table('bank_accounts')->where('status', 'active')->where('chart_of_account_id', '!=', $accountId)->orderBy('display_name')->get(['id', 'display_name'])
            : collect();

        return view('accounting::phase7.reconcile.index', compact('accounts', 'accountId', 'account', 'ws', 'mapping', 'selected', 'candidates', 'bankAccount', 'postAccounts', 'transferTargets'));
    }

    public function import(Request $request)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $data = $request->validate([
            'chart_of_account_id' => 'required|integer',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'statement_from' => 'nullable|date',
            'statement_to' => 'nullable|date',
            'opening_balance' => 'nullable|numeric',
            'closing_balance' => 'nullable|numeric',
            'map' => 'nullable|array',
            'map.*' => 'nullable|integer|min:1|max:50',
            'save_mapping' => 'nullable|boolean',
        ]);
        $accountId = (int) $data['chart_of_account_id'];

        // Mapping typed in the form (1-based column numbers) → 0-based; else the saved one; else header auto-detect.
        $typed = array_filter($data['map'] ?? [], fn ($v) => $v !== null && $v !== '');
        $map = $typed ? array_map(fn ($v) => (int) $v - 1, $typed) : BankReconciliationService::mappingFor($accountId);

        try {
            if ($typed && $request->boolean('save_mapping')) {
                BankReconciliationService::saveMapping($accountId, $map, Auth::id());
            }
            $file = $request->file('file');
            $rows = BankStatementImportService::parseUploadedFile($file, $map ?: null);
            $result = BankStatementImportService::importWithErrors($accountId, $rows, $file->getClientOriginalName(), 0, Auth::id(), true);
            $import = $result['import'];
            $dates = $import->lines()->pluck('txn_date')->filter();
            $import->forceFill([
                'statement_from' => $data['statement_from'] ?? $dates->min(),
                'statement_to' => $data['statement_to'] ?? $dates->max(),
                'opening_balance' => $data['opening_balance'] ?? null,
                'closing_balance' => $data['closing_balance'] ?? null,
            ])->save();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $msg = $import->row_count . ' line(s) imported.';
        if ($result['errors']) {
            $msg .= ' Skipped: ' . implode(' ', array_slice($result['errors'], 0, 5));
        }

        return redirect(url('accounting/phase7/reconcile?account_id=' . $accountId))->with('status', $msg);
    }

    public function saveBalance(Request $request, int $importId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $data = $request->validate(['statement_to' => 'required|date', 'closing_balance' => 'required|numeric', 'opening_balance' => 'nullable|numeric']);
        DB::table('bank_statement_imports')->where('id', $importId)->update($data + ['updated_at' => now()]);

        return back()->with('status', 'Statement balance saved.');
    }

    public function match(Request $request, int $lineId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $data = $request->validate(['sources' => 'required|array|min:1', 'sources.*' => 'string', 'allow_fee' => 'nullable|boolean']);
        $line = BankStatementLine::query()->with('import')->findOrFail($lineId);
        $sources = array_map(function ($s) {
            [$type, $id] = array_pad(explode(':', $s, 2), 2, 0);

            return ['type' => $type, 'id' => (int) $id];
        }, $data['sources']);

        try {
            $res = BankReconciliationService::match($line, $sources, $request->boolean('allow_fee'), Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['match' => $e->getMessage()]);
        }

        return redirect(url('accounting/phase7/reconcile?account_id=' . $line->import->chart_of_account_id))
            ->with('status', 'Matched ' . count($sources) . ' transaction(s)' . ($res['fee'] > 0 ? '; bank fee Rs ' . number_format($res['fee'], 2) . ' posted' : '') . '.');
    }

    /** New item on the statement (bank charge, interest …): post it and match. */
    public function postLine(Request $request, int $lineId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $data = $request->validate(['account_id' => 'required|integer', 'description' => 'nullable|string|max:190']);
        $line = BankStatementLine::query()->with('import')->findOrFail($lineId);
        try {
            \App\Services\Accounting\BankAccountService::categoriseAndPost($line, (int) $data['account_id'], $data['description'] ?? null, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['match' => $e->getMessage()]);
        }

        return redirect(url('accounting/phase7/reconcile?account_id=' . $line->import->chart_of_account_id))->with('status', 'Posted and matched.');
    }

    /** Money moved between two of our own bank accounts. */
    public function transferLine(Request $request, int $lineId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $data = $request->validate(['bank_account_id' => 'required|integer']);
        $line = BankStatementLine::query()->with('import')->findOrFail($lineId);
        try {
            \App\Services\Accounting\BankAccountService::transfer($line, (int) $data['bank_account_id'], Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['match' => $e->getMessage()]);
        }

        return redirect(url('accounting/phase7/reconcile?account_id=' . $line->import->chart_of_account_id))->with('status', 'Transfer recorded and matched.');
    }

    public function unmatch(int $lineId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $line = BankStatementLine::query()->findOrFail($lineId);
        try {
            BankReconciliationService::unmatch($line, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['match' => $e->getMessage()]);
        }

        return back()->with('status', 'Match removed.');
    }

    public function exclude(Request $request, int $lineId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $line = BankStatementLine::query()->findOrFail($lineId);
        try {
            BankMatchingService::exclude($line, $request->input('reason') ?: 'Excluded from reconciliation', Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['match' => $e->getMessage()]);
        }

        return back()->with('status', 'Line excluded.');
    }

    private function bankAccounts()
    {
        // Registered bank accounts first (Banking → Bank accounts); id = ledger account id.
        if (\Illuminate\Support\Facades\Schema::hasTable('bank_accounts') && DB::table('bank_accounts')->where('status', 'active')->exists()) {
            return DB::table('bank_accounts as b')->join('chart_of_accounts as c', 'c.id', '=', 'b.chart_of_account_id')
                ->where('b.status', 'active')->orderBy('b.display_name')
                ->get(['c.id', 'c.gl_code', 'b.display_name as name', 'b.id as bank_account_id']);
        }

        return DB::table('chart_of_accounts')
            ->where('active', 1)
            ->where(fn ($q) => $q->whereIn('gl_code', ['1000', '1010', '1150'])->orWhere('name', 'like', '%bank%'))
            ->orderBy('gl_code')
            ->get(['id', 'gl_code', 'name']);
    }
}

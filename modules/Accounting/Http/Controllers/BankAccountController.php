<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\BankAccountService;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\BankStatementImportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Bank accounts — one page per real bank / card account: import → review & match → reconcile → history → settings.
 */
class BankAccountController extends Controller
{
    use RequiresAccountingPermission;

    private const TMP = 'bank-imports/tmp';

    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');
        $accounts = BankAccountService::summaries($showArchived);

        return view('accounting::banking.index', compact('accounts', 'showArchived'));
    }

    public function create()
    {
        $this->requireAccountingAction('accounting.accounts.manage');
        $linked = DB::table('bank_accounts')->pluck('chart_of_account_id')->all();
        $ledgers = DB::table('chart_of_accounts')->where('active', 1)
            ->whereIn('account_type', ['asset', 'liability'])
            ->whereNotIn('id', $linked)
            ->orderBy('gl_code')->get(['id', 'gl_code', 'name', 'account_type']);
        $balances = $ledgers->mapWithKeys(fn ($l) => [$l->id => round((float) DB::table('journal_entries')->where('chart_of_account_id', $l->id)->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as b')->value('b'), 2)]);

        return view('accounting::banking.create', compact('ledgers', 'balances'));
    }

    public function store(Request $request)
    {
        $this->requireAccountingAction('accounting.accounts.manage');
        $data = $request->validate([
            'bank_name' => 'required|string|max:80',
            'display_name' => 'required|string|max:120',
            'account_type' => 'required|in:current,savings,credit_card',
            'account_number' => 'nullable|string|max:40',
            'currency' => 'nullable|in:LKR',
            'ledger_mode' => 'required|in:new,existing',
            'chart_of_account_id' => 'nullable|required_if:ledger_mode,existing|integer',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date|required_with:opening_balance',
        ]);
        try {
            $acc = BankAccountService::create($data, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['account' => $e->getMessage()]);
        }

        return redirect(url('accounting/banking/' . $acc->id . '?tab=import'))->with('status', $acc->display_name . ' added. Import its first bank statement.');
    }

    public function show(Request $request, int $id)
    {
        $acc = BankAccountService::summaries(true)->firstWhere('id', $id);
        abort_unless($acc, 404);
        $tab = (string) $request->get('tab', 'overview');
        $ledger = DB::table('chart_of_accounts')->where('id', $acc->chart_of_account_id)->first();
        $mapping = BankReconciliationService::mappingFor((int) $acc->chart_of_account_id);
        $imports = DB::table('bank_statement_imports')->where('chart_of_account_id', $acc->chart_of_account_id)->where('status', 'imported')->orderByDesc('id')->limit(20)->get();
        $reconciliations = DB::table('bank_reconciliations')->where('bank_account_id', $id)->orderByDesc('statement_date')->orderByDesc('id')->get();
        $lastCompleted = $reconciliations->firstWhere('status', 'completed');

        // Reconcile tab: statement date + balance → uncleared lines.
        $rec = null;
        if ($tab === 'reconcile' && $request->filled(['statement_date', 'statement_balance'])) {
            $date = (string) $request->get('statement_date');
            $rec = [
                'date' => $date,
                'balance' => round((float) $request->get('statement_balance'), 2),
                'beginning' => BankAccountService::beginningBalance($acc),
                'lines' => BankAccountService::unclearedLines($acc, $date),
            ];
        }
        $users = DB::table('users')->whereIn('id', $reconciliations->pluck('completed_by')->filter()->unique())->pluck('name', 'id');

        return view('accounting::banking.show', compact('acc', 'tab', 'ledger', 'mapping', 'imports', 'reconciliations', 'lastCompleted', 'rec', 'users'));
    }

    // ------------------------------------------------------------------ import: upload → preview → confirm

    public function importPreview(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $acc = BankAccountService::find($id);
        abort_if($acc->status !== 'active', 403, 'This bank account is archived.');
        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'map' => 'nullable|array',
            'map.*' => 'nullable|integer|min:1|max:50',
            'save_mapping' => 'nullable|boolean',
            'closing_balance' => 'nullable|numeric',
            'statement_to' => 'nullable|date',
        ]);
        $typed = array_filter($data['map'] ?? [], fn ($v) => $v !== null && $v !== '');
        $map = $typed ? array_map(fn ($v) => (int) $v - 1, $typed) : BankReconciliationService::mappingFor((int) $acc->chart_of_account_id);

        $file = $request->file('file');
        try {
            $rows = BankStatementImportService::parseUploadedFile($file, $map ?: null);
            $preview = BankStatementImportService::preview((int) $acc->chart_of_account_id, $rows);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $token = (string) Str::uuid();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
        Storage::disk('local')->put(self::TMP . "/{$token}.{$ext}", file_get_contents($file->getRealPath()));
        Storage::disk('local')->put(self::TMP . "/{$token}.json", json_encode([
            'ext' => $ext, 'name' => $file->getClientOriginalName(), 'map' => $map, 'typed' => (bool) $typed,
            'save_mapping' => $request->boolean('save_mapping'), 'closing_balance' => $data['closing_balance'] ?? null, 'statement_to' => $data['statement_to'] ?? null,
        ]));

        return view('accounting::banking.import_preview', compact('acc', 'preview', 'token') + ['meta' => ['name' => $file->getClientOriginalName(), 'closing_balance' => $data['closing_balance'] ?? null, 'statement_to' => $data['statement_to'] ?? null]]);
    }

    public function importConfirm(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $acc = BankAccountService::find($id);
        $token = (string) $request->validate(['token' => 'required|uuid'])['token'];
        $metaPath = self::TMP . "/{$token}.json";
        if (!Storage::disk('local')->exists($metaPath)) {
            return redirect(url('accounting/banking/' . $id . '?tab=import'))->withErrors(['file' => 'The preview expired — upload the file again.']);
        }
        $meta = json_decode(Storage::disk('local')->get($metaPath), true);
        $path = Storage::disk('local')->path(self::TMP . "/{$token}.{$meta['ext']}");

        try {
            if ($meta['typed'] && $meta['save_mapping'] && $meta['map']) {
                BankReconciliationService::saveMapping((int) $acc->chart_of_account_id, $meta['map'], Auth::id());
            }
            $upload = new \Illuminate\Http\UploadedFile($path, $meta['name'], null, null, true);
            $rows = BankStatementImportService::parseUploadedFile($upload, $meta['map'] ?: null);
            $result = BankStatementImportService::importWithErrors((int) $acc->chart_of_account_id, $rows, $meta['name'], 0, Auth::id(), true);
            $import = $result['import'];
            $dates = $import->lines()->pluck('txn_date')->filter();
            $import->forceFill([
                'statement_from' => $dates->min(),
                'statement_to' => $meta['statement_to'] ?: $dates->max(),
                'closing_balance' => $meta['closing_balance'],
            ])->save();
        } catch (\Throwable $e) {
            return redirect(url('accounting/banking/' . $id . '?tab=import'))->withErrors(['file' => $e->getMessage()]);
        } finally {
            Storage::disk('local')->delete([$metaPath, self::TMP . "/{$token}.{$meta['ext']}"]);
        }

        return redirect(url('accounting/phase7/reconcile?account_id=' . $acc->chart_of_account_id))
            ->with('status', $import->row_count . ' new line(s) imported into ' . $acc->display_name . '. Match them below.');
    }

    // ------------------------------------------------------------------ reconcile

    public function reconcileFinish(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        $acc = BankAccountService::find($id);
        $data = $request->validate([
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'cleared' => 'nullable|array',
            'cleared.*' => 'integer',
            'notes' => 'nullable|string|max:1000',
        ]);
        try {
            BankAccountService::finishReconciliation($acc, $data['statement_date'], (float) $data['statement_balance'], $data['cleared'] ?? [], $data['notes'] ?? null, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['reconcile' => $e->getMessage()]);
        }

        return redirect(url('accounting/banking/' . $id . '?tab=history'))->with('status', 'Reconciled to ' . $data['statement_date'] . ' — difference 0.00.');
    }

    public function reconciliation(int $id, int $recId)
    {
        $acc = BankAccountService::find($id);
        $rec = DB::table('bank_reconciliations')->where('id', $recId)->where('bank_account_id', $id)->first();
        abort_unless($rec, 404);
        $lines = DB::table('bank_cleared_entries as c')->join('journal_entries as j', 'j.id', '=', 'c.journal_entry_id')
            ->where('c.bank_reconciliation_id', $recId)->orderBy('j.date')->get(['c.amount', 'j.date', 'j.name', 'j.reference', 'j.transaction_number', 'j.source_type', 'j.source_id']);
        $by = $rec->completed_by ? DB::table('users')->where('id', $rec->completed_by)->value('name') : null;

        return view('accounting::banking.reconciliation', compact('acc', 'rec', 'lines', 'by'));
    }

    public function undoReconciliation(int $id, int $recId)
    {
        $this->requireAccountingAction('accounting.banking.reconcile');
        try {
            BankAccountService::undoLastReconciliation(BankAccountService::find($id), $recId, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reconcile' => $e->getMessage()]);
        }

        return back()->with('status', 'Reconciliation undone — its lines are uncleared again.');
    }

    // ------------------------------------------------------------------ settings

    public function updateSettings(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.accounts.manage');
        $data = $request->validate([
            'display_name' => 'required|string|max:120',
            'bank_name' => 'required|string|max:80',
            'account_number' => 'nullable|string|max:40',
            'map' => 'nullable|array',
            'map.*' => 'nullable|integer|min:1|max:50',
        ]);
        $acc = BankAccountService::find($id);
        try {
            BankAccountService::updateSettings($id, $data);
            $typed = array_filter($data['map'] ?? [], fn ($v) => $v !== null && $v !== '');
            if ($typed) {
                BankReconciliationService::saveMapping((int) $acc->chart_of_account_id, array_map(fn ($v) => (int) $v - 1, $typed), Auth::id());
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['settings' => $e->getMessage()]);
        }

        return back()->with('status', 'Settings saved.');
    }

    public function archive(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.accounts.manage');
        try {
            BankAccountService::archive($id, $request->boolean('accept_balance'), Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['settings' => $e->getMessage()]);
        }

        return redirect(url('accounting/banking'))->with('status', 'Bank account archived. Its ledger history stays in all reports.');
    }

    public function restore(int $id)
    {
        $this->requireAccountingAction('accounting.accounts.manage');
        BankAccountService::restore($id);

        return back()->with('status', 'Bank account restored.');
    }

    public function disconnectFeed(int $id)
    {
        $this->requireAccountingAction('accounting.accounts.manage');
        BankAccountService::disconnectFeed($id);

        return back()->with('status', 'Bank feed disconnected — statements come from manual imports.');
    }
}

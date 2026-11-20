<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\RecodeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 6.6 — Find & recode.
 */
class RecodeController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        $this->requireAccountingAction('accounting.recode', false);
        $data = $request->validate([
            'account_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'contains' => 'nullable|string|max:80',
            'type' => 'nullable|string|max:60',
        ]);
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $accountId = $data['account_id'] ?? null;
        $accounts = DB::table('chart_of_accounts')->where('active', 1)->orderBy('gl_code')->get(['id', 'gl_code', 'name']);
        $lines = $accountId ? RecodeService::candidates((int) $accountId, $from, $to, $data['contains'] ?? null, $data['type'] ?? null) : collect();
        $types = $accountId
            ? DB::table('journal_entries')->where('chart_of_account_id', $accountId)->whereNotNull('transaction_type')->distinct()->orderBy('transaction_type')->pluck('transaction_type')
            : collect();

        return view('accounting::phase6.recode.index', compact('accounts', 'accountId', 'from', 'to', 'lines', 'types', 'data'));
    }

    public function store(Request $request)
    {
        $this->requireAccountingAction('accounting.recode');
        $data = $request->validate([
            'line_ids' => 'required|array|min:1',
            'line_ids.*' => 'integer',
            'target_account_id' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);
        $result = RecodeService::recode($data['line_ids'], (int) $data['target_account_id'], $data['reason']);

        return back()
            ->with('status', $result['done'] . ' line(s) recoded.')
            ->with('recode_skipped', $result['skipped']);
    }
}

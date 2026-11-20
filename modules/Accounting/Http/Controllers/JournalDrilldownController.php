<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\DocumentJournalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Phase 6.2 — drill-down from any report figure: account → transactions → journal → source document.
 */
class JournalDrilldownController extends Controller
{
    public function transaction(string $transactionNumber)
    {
        $txn = DocumentJournalService::transaction($transactionNumber);
        abort_unless($txn, 404);

        return view('accounting::phase6.journal.transaction', compact('txn'));
    }

    public function accountTransactions(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'nullable|integer',
            'gl' => 'nullable|string|max:20',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);
        $from = $data['from'] ?? date('Y-01-01');
        $to = $data['to'] ?? date('Y-m-d');
        $accountId = $data['account_id'] ?? null;
        if (!$accountId && !empty($data['gl'])) {
            $accountId = \App\Services\AccountingService::accountId($data['gl']);
        }

        $accounts = DB::table('chart_of_accounts')->where('active', 1)->orderBy('gl_code')->get(['id', 'gl_code', 'name']);
        $activity = $accountId ? DocumentJournalService::accountActivity((int) $accountId, $from, $to) : null;

        return view('accounting::phase6.journal.account', compact('accounts', 'accountId', 'activity', 'from', 'to'));
    }
}

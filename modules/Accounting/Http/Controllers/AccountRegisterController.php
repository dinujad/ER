<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\AccountRegisterService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\ChartOfAccount;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountRegisterController extends Controller
{
    public function index(Request $request)
    {
        $accounts = ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get();
        $accountId = (int) $request->get('account_id');
        $from = $request->get('from') ?: date('Y-01-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $register = null;
        $account = null;

        if ($accountId > 0) {
            $account = ChartOfAccount::findOrFail($accountId);
            $register = AccountRegisterService::register($accountId, $from, $to, $request->location_id ? (int) $request->location_id : null);
        }

        return view('accounting::phase2.account_register.index', compact('accounts', 'account', 'register', 'from', 'to', 'accountId'));
    }

    public function show(Request $request, $accountId)
    {
        $request->merge(['account_id' => $accountId]);

        return $this->index($request);
    }

    public function exportCsv(Request $request)
    {
        $accountId = (int) $request->get('account_id');
        abort_if($accountId <= 0, 422, 'Account required');

        $from = $request->get('from') ?: date('Y-01-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $register = AccountRegisterService::register($accountId, $from, $to);
        $account = ChartOfAccount::findOrFail($accountId);

        return new StreamedResponse(function () use ($register, $account, $from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Account Register', $account->gl_code, $account->name, 'LKR', $from, $to]);
            fputcsv($out, ['Opening Balance', $register['opening']]);
            fputcsv($out, ['Date', 'Journal #', 'Description', 'Reference', 'Debit', 'Credit', 'Balance', 'Status']);
            foreach ($register['rows'] as $row) {
                fputcsv($out, [
                    $row->date, $row->transaction_number, $row->description, $row->reference,
                    $row->debit, $row->credit, $row->running_balance, $row->status,
                ]);
            }
            fputcsv($out, ['Closing Balance', $register['closing']]);
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="account-register-'.$accountId.'.csv"',
        ]);
    }
}

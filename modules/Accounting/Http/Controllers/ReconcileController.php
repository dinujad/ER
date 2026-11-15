<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\AccountingService as ErpAccountingService;
use App\Business;
use Modules\Accounting\Services\FlashService;
use Modules\Accounting\Entities\PaymentDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;

class ReconcileController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $chart_of_accounts = ChartOfAccount::with('account_subtype')->forBusiness()->orderBy('gl_code')->get();
        return view('accounting::reconcile.index', compact('chart_of_accounts'));
    }

    public function start_reconcile(Request $request)
    {
        $request->validate([
            'opening_balance' => ['required'],
            'ending_balance' => ['required'],
            'ending_date' => ['required'],
            'chart_of_account_id' => ['required'],
        ]);

        $computed_data = $request->only(['opening_balance', 'ending_balance', 'ending_date']);
        $chart_of_account = ChartOfAccount::whereHas('journal_entries', function ($query) use ($request) {
            $query->where('date', '<=', $request->ending_date);
        })
            ->forBusiness()
            ->find($request->chart_of_account_id);

        if (!$chart_of_account) {
            (new FlashService())->onWarning(trans('accounting::general.chart_of_account_has_no_journal_entries', [
                'chart_of_account_name' => $request->chart_of_account_name
            ]));
            return redirect('accounting/reconcile')->withInput();
        }

        $difference = $request->ending_balance - $chart_of_account->current_balance;

        $data = [
            'chart_of_accounts' => ChartOfAccount::with('account_subtype')->orderBy('gl_code')->get(),
            'chart_of_account_id' => $chart_of_account->id,
        ];

        $computed_data['total_debit'] = $chart_of_account->journal_entries_not_reversed->sum('debit');
        $computed_data['no_debit'] = $chart_of_account->journal_entries_not_reversed->where('debit', '!=', null)->count();
        $computed_data['total_credit'] = $chart_of_account->journal_entries_not_reversed->sum('credit');
        $computed_data['no_credit'] = $chart_of_account->journal_entries_not_reversed->where('credit', '!=', null)->count();

        $computed_data['currency_code'] = currency_code();
        $computed_data['chart_of_account'] = $chart_of_account;
        $computed_data['difference'] = $difference;

        return view('accounting::reconcile.start_reconcile', array_merge($computed_data, $data));
    }

    public function store_reconcile(Request $request)
    {
        $request->validate([
            'ending_balance' => ['required'],
            'difference' => ['required'],
            'chart_of_account_id' => ['required'],
        ]);

        try {
            DB::beginTransaction();

            $business = Business::find(session('business.id')) ?? Business::firstOrCreate(['id' => 1], ['fy_start_month' => 1]);

            $today = date('Y-m-d');
            $difference = (float) $request->difference;
            $amount = abs($difference);
            $equityId = ErpAccountingService::accountId(ErpAccountingService::GL_OWNER_EQUITY);
            if (!$equityId) {
                throw new \RuntimeException('Owner equity account (3000) is missing.');
            }

            if ($amount > 0) {
                $selectedId = (int) $request->chart_of_account_id;
                $debitId = $difference >= 0 ? $equityId : $selectedId;
                $creditId = $difference >= 0 ? $selectedId : $equityId;

                ErpAccountingService::postEntry(
                    $debitId,
                    $creditId,
                    $amount,
                    $today,
                    'Account reconciliation adjustment',
                    'RECON-' . $request->chart_of_account_id,
                    'reconcile_entry',
                    ['created_by_id' => Auth::id()]
                );
            }

            // Update the reconcile opening balance
            $chart_of_account = ChartOfAccount::findOrFail($request->chart_of_account_id);
            $chart_of_account->update([
                'reconcile_opening_balance' => $request->ending_balance
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        (new FlashService())->onSuccess(trans('accounting::general.reconcile_success', ['account' => $chart_of_account->name]));
        return redirect('accounting/reconcile');
    }

    public function undo_reconcile(Request $request)
    {
        $request->validate([
            'chart_of_account_id' => ['required'],
            'last_reconcile_transaction_id' => ['required'],
        ]);

        $chart_of_account = ChartOfAccount::findOrFail($request->chart_of_account_id);

        try {
            $journal_entry_to_reverse = JournalEntry::findOrFail($request->last_reconcile_transaction_id);
            ErpAccountingService::reverseEntry($journal_entry_to_reverse->transaction_number);

            // Update the reconcile opening balance to be the current balance 
            $chart_of_account->update([
                'reconcile_opening_balance' => $chart_of_account->current_balance
            ]);
        } catch (\Exception $e) {
            (new FlashService())->onException($e);
            return redirect('accounting/reconcile');
        }

        (new FlashService())->onSuccess(trans('accounting::general.undo_reconcile_success', ['account' => $chart_of_account->name]));
        return redirect('accounting/reconcile');
    }
}

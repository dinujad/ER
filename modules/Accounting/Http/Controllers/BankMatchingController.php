<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use App\Services\Accounting\Ai\BankMatchingAssistService;
use App\Services\Accounting\BankMatchingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Services\FlashService;

class BankMatchingController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Schema::hasTable('chart_of_accounts')
            ? ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get()
            : collect();
        $accountId = (int) $request->get('chart_of_account_id');
        $tab = $request->get('tab', 'unmatched');
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $q = trim((string) $request->get('q'));

        $lines = collect();
        $suggestions = [];

        if ($accountId > 0) {
            $query = BankStatementLine::query()
                ->whereHas('import', fn ($w) => $w->where('chart_of_account_id', $accountId))
                ->whereBetween('txn_date', [$from, $to])
                ->orderByDesc('txn_date')
                ->orderByDesc('id');

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('description', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%");
                });
            }

            match ($tab) {
                'matched' => $query->where('match_status', 'matched'),
                'excluded' => $query->where('match_status', 'excluded'),
                'suggested' => $query->where('match_status', 'unmatched'),
                'all' => null,
                default => $query->where('match_status', 'unmatched'),
            };

            $lines = $query->paginate(25)->withQueryString();

            if ($tab === 'suggested' || $tab === 'unmatched') {
                $assist = app(BankMatchingAssistService::class);
                $businessId = (int) (session('business.id') ?? Auth::user()?->business_id ?? 0);
                foreach ($lines as $line) {
                    $assisted = $assist->assist($line, $accountId, $businessId);
                    $suggestions[$line->id] = $assisted['suggestions'];
                    $line->unmatched_explanation = $assisted['unmatched_explanation'];
                }
            }
        }

        return view('accounting::phase2.bank_matching.index', compact(
            'accounts', 'accountId', 'tab', 'from', 'to', 'lines', 'suggestions', 'q'
        ));
    }

    public function confirm(Request $request, $id)
    {
        $request->validate([
            'source_type' => 'required|string',
            'source_id' => 'required|integer',
        ]);

        try {
            BankMatchingService::confirmMatch(
                BankStatementLine::findOrFail($id),
                $request->source_type,
                (int) $request->source_id,
                Auth::id()
            );
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect()->back();
    }

    public function unmatch($id)
    {
        try {
            BankMatchingService::unmatch(BankStatementLine::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect()->back();
    }

    public function exclude(Request $request, $id)
    {
        try {
            BankMatchingService::exclude(BankStatementLine::findOrFail($id), $request->get('reason'), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect()->back();
    }
}

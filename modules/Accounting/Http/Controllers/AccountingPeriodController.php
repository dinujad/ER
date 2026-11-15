<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AccountingPeriod;
use App\Services\Accounting\AccountingPeriodService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Services\FlashService;

class AccountingPeriodController extends Controller
{
    public function index()
    {
        $periods = AccountingPeriod::query()->orderByDesc('starts_on')->paginate(25);

        return view('accounting::phase2.periods.index', compact('periods'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.periods.manage'))) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
        ]);

        AccountingPeriod::create([
            'business_id' => 0,
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => 'open',
        ]);

        (new FlashService())->onSave();

        return redirect('accounting/periods');
    }

    public function close($id)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.periods.manage'))) {
            abort(403);
        }

        AccountingPeriodService::close(AccountingPeriod::findOrFail($id), Auth::id());
        (new FlashService())->onSave();

        return redirect('accounting/periods');
    }

    public function reopen(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.periods.manage'))) {
            abort(403);
        }

        AccountingPeriodService::reopen(AccountingPeriod::findOrFail($id), Auth::id(), $request->get('reason'));
        (new FlashService())->onSave();

        return redirect('accounting/periods');
    }
}

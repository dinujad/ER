<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\MonthEndCloseService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 7 — month-end close checklist → sign-off → lock.
 */
class MonthEndCloseController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        $key = (string) $request->get('period', now()->subMonthNoOverflow()->format('Y-m'));
        try {
            $period = MonthEndCloseService::period($key);
        } catch (\RuntimeException $e) {
            $key = now()->subMonthNoOverflow()->format('Y-m');
            $period = MonthEndCloseService::period($key);
        }
        $checks = MonthEndCloseService::checks($key);
        $review = MonthEndCloseService::review($key);
        $periodRow = MonthEndCloseService::periodRow($key);
        $history = Schema::hasTable('acct_period_close_reviews')
            ? DB::table('acct_period_close_reviews')->orderByDesc('period_key')->limit(12)->get()
            : collect();
        $users = DB::table('users')->whereIn('id', array_filter([$review->signed_off_by ?? null, $review->locked_by ?? null]))->pluck('name', 'id');

        return view('accounting::phase7.close.index', compact('key', 'period', 'checks', 'review', 'periodRow', 'history', 'users'));
    }

    public function signOff(Request $request, string $key)
    {
        $this->requireAccountingAction('accounting.periods.manage');
        $data = $request->validate(['notes' => 'nullable|string|max:2000', 'accept_exceptions' => 'nullable|boolean']);
        try {
            MonthEndCloseService::signOff($key, $data['notes'] ?? null, Auth::id(), $request->boolean('accept_exceptions'));
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['close' => $e->getMessage()]);
        }

        return back()->with('status', 'Checklist signed off. You can now lock the period.');
    }

    public function lock(string $key)
    {
        $this->requireAccountingAction('accounting.periods.manage');
        try {
            MonthEndCloseService::lock($key, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['close' => $e->getMessage()]);
        }

        return back()->with('status', 'Period locked — nothing can be posted into ' . MonthEndCloseService::period($key)['label'] . ' now.');
    }

    public function reopen(Request $request, string $key)
    {
        $this->requireAccountingAction('accounting.periods.manage');
        $data = $request->validate(['reason' => 'required|string|max:255']);
        MonthEndCloseService::reopen($key, $data['reason'], Auth::id());

        return back()->with('status', 'Period reopened. Sign off again before locking.');
    }
}

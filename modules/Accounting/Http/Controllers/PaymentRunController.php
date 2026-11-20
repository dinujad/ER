<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\PaymentRunService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 7 — supplier payment runs.
 */
class PaymentRunController extends Controller
{
    use RequiresAccountingPermission;

    public function __construct(private readonly PaymentRunService $runs)
    {
    }

    public function index()
    {
        $runs = DB::table('acct_payment_runs')->orderByDesc('id')->paginate(25);

        return view('accounting::phase7.payment_runs.index', compact('runs'));
    }

    public function store(Request $request)
    {
        $this->requireAccountingAction('accounting.ap.manage');
        $data = $request->validate([
            'purchase_ids' => 'required|array|min:1',
            'purchase_ids.*' => 'integer',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'bank_account_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:255',
        ]);
        try {
            $run = $this->runs->create($data['purchase_ids'], $data['payment_date'], $data['bank_account_id'] ?? null, $data['notes'] ?? null, Auth::id(), $data['amounts'] ?? []);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['run' => $e->getMessage()]);
        }

        return redirect(url('accounting/phase7/payment-runs/' . $run->id))->with('status', 'Payment run ' . $run->run_no . ' created — review and approve it.');
    }

    public function show(int $id)
    {
        $run = $this->runs->find($id);
        $items = $this->runs->items($id);
        $bank = $run->bank_account_id ? DB::table('chart_of_accounts')->where('id', $run->bank_account_id)->first() : null;
        $history = \Illuminate\Support\Facades\Schema::hasTable('audit_logs')
            ? \App\Models\AuditLog::query()->where('auditable_type', 'payment_run')->where('auditable_id', $id)->orderByDesc('id')->get()
            : collect();
        $formats = PaymentRunService::FORMATS;

        return view('accounting::phase7.payment_runs.show', compact('run', 'items', 'bank', 'history', 'formats'));
    }

    public function approve(int $id)
    {
        $this->requireAccountingAction('accounting.bills.approve');

        return $this->attempt(fn () => $this->runs->approve($id, Auth::id()), 'Payment run approved. Export the bank file next.');
    }

    public function export(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.ap.manage');
        $format = (string) $request->get('format', 'generic_csv');
        try {
            $file = $this->runs->export($id, $format);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return response($file['content'], 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $file['filename'] . '"',
        ]);
    }

    public function fail(Request $request, int $id, int $itemId)
    {
        $this->requireAccountingAction('accounting.ap.manage');
        $data = $request->validate(['reason' => 'required|string|max:255']);

        return $this->attempt(fn () => $this->runs->markFailed($id, $itemId, $data['reason']), 'Line marked as failed — the bill stays unpaid.');
    }

    public function confirm(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.ap.manage');
        $data = $request->validate(['paid_on' => 'nullable|date']);
        try {
            $res = $this->runs->confirmPaid($id, $data['paid_on'] ?? null, Auth::id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return back()->with('status', $res['paid'] . ' bill payment(s) recorded.' . ($res['skipped'] ? ' Not paid: ' . implode('; ', $res['skipped']) : ''));
    }

    public function cancel(int $id)
    {
        $this->requireAccountingAction('accounting.ap.manage');

        return $this->attempt(fn () => $this->runs->cancel($id), 'Payment run cancelled.');
    }

    private function attempt(callable $fn, string $ok)
    {
        try {
            $fn();
        } catch (\RuntimeException $e) {
            return back()->withErrors(['run' => $e->getMessage()]);
        }

        return back()->with('status', $ok);
    }
}

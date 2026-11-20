<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\RecurringAccountingRun;
use App\Services\Accounting\ReceivablesService;
use App\Services\Accounting\RecurringAccountingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 7 — reminder schedule + delivery history, and the recurring documents queue.
 */
class ReceivablesAutomationController extends Controller
{
    use RequiresAccountingPermission;

    public function reminders(Request $request, ReceivablesService $receivables)
    {
        $status = $request->get('status');
        $logs = Schema::hasTable('acct_reminder_logs')
            ? DB::table('acct_reminder_logs as l')
                ->leftJoin('orders as o', 'o.id', '=', 'l.order_id')
                ->leftJoin('customers as c', 'c.id', '=', 'l.customer_id')
                ->when($status, fn ($q, $v) => $q->where('l.status', $v))
                ->select('l.*', 'o.invoice_number', DB::raw('COALESCE(c.business_name, c.name) as customer'))
                ->orderByDesc('l.id')->paginate(40)->withQueryString()
            : collect();
        $stats = Schema::hasTable('acct_reminder_logs')
            ? DB::table('acct_reminder_logs')->where('created_at', '>=', now()->subDays(30))->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')
            : collect();
        $preview = $receivables->runReminderSchedule(true);

        return view('accounting::phase7.receivables.reminders', compact('logs', 'stats', 'preview', 'status'));
    }

    public function runReminders(ReceivablesService $receivables)
    {
        $this->requireAccountingAction('accounting.reminders.send');
        $s = $receivables->runReminderSchedule(false);

        return back()->with('status', "Reminders sent: {$s['sent']}" . ($s['failed'] ? ", failed: {$s['failed']}" : '') . " (checked {$s['checked']} overdue invoices).");
    }

    public function recurringQueue()
    {
        $failed = RecurringAccountingRun::query()->where('status', 'failed')->orderByDesc('occurrence_date')->limit(100)->get();
        $drafts = RecurringAccountingRun::query()->whereIn('generated_type', ['order', 'purchase', 'manual_journal'])
            ->where('status', '!=', 'failed')->orderByDesc('occurrence_date')->limit(200)->get()
            ->map(function ($run) {
                $run->doc = $this->generatedDocument($run);

                return $run;
            })
            ->filter(fn ($run) => $run->doc && $run->doc['open'])
            ->values();
        $templates = DB::table('accounting_recurring_templates')->whereIn('id', $failed->pluck('template_id')->merge($drafts->pluck('template_id'))->unique())->pluck('name', 'id');

        return view('accounting::phase7.receivables.recurring_queue', compact('failed', 'drafts', 'templates'));
    }

    public function retry(int $runId)
    {
        $this->requireAccountingAction('accounting.recurring.manage');
        try {
            $run = RecurringAccountingService::retryFailedRun($runId, Auth::id());
        } catch (\Throwable $e) {
            return back()->withErrors(['retry' => $e->getMessage()]);
        }

        return back()->with($run->status === 'failed' ? 'error' : 'status', $run->status === 'failed' ? 'Still failing: ' . $run->message : 'Run created: ' . $run->message)
            ->withErrors($run->status === 'failed' ? ['retry' => 'Still failing: ' . $run->message] : []);
    }

    /** @return array{label: string, status: string, open: bool, actions: array<int, array{0: string, 1: string}>}|null */
    private function generatedDocument(RecurringAccountingRun $run): ?array
    {
        $id = (int) $run->generated_id;
        if ($run->generated_type === 'order') {
            $o = DB::table('orders')->where('id', $id)->first(['id', 'order_number', 'status', 'invoice_number']);
            if (!$o) {
                return null;
            }
            $open = !$o->invoice_number && !in_array($o->status, ['cancelled'], true);

            return [
                'label' => 'Draft invoice ' . $o->order_number, 'status' => $o->status, 'open' => $open,
                'actions' => array_values(array_filter([
                    \Illuminate\Support\Facades\Route::has('orders.show') ? ['Open order — add items & Issue Invoice', route('orders.show', $o->id)] : null,
                    ['Accounting view', url('accounting/phase6/invoices/' . $o->id)],
                ])),
            ];
        }
        if ($run->generated_type === 'purchase') {
            $p = DB::table('purchases')->where('id', $id)->first(['id', 'purchase_no', 'status']);
            if (!$p) {
                return null;
            }

            return [
                'label' => 'Draft bill ' . $p->purchase_no, 'status' => $p->status, 'open' => $p->status === 'draft',
                'actions' => array_values(array_filter([
                    \Illuminate\Support\Facades\Route::has('purchases.show') ? ['Open purchase — confirm', route('purchases.show', $p->id)] : null,
                    ['Bill view / approve', url('accounting/phase6/bills/' . $p->id)],
                ])),
            ];
        }
        $j = Schema::hasTable('accounting_manual_journals') ? DB::table('accounting_manual_journals')->where('id', $id)->first(['id', 'journal_number', 'status']) : null;
        if (!$j) {
            return null;
        }

        return [
            'label' => 'Journal ' . $j->journal_number, 'status' => $j->status, 'open' => !in_array($j->status, ['posted', 'reversed', 'rejected'], true),
            'actions' => [['Open journal — submit / post', url('accounting/manual-journals/' . $j->id)]],
        ];
    }
}

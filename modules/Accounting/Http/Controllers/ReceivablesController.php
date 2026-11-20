<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Order;
use App\Services\Accounting\ReceivablesService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 6.4 — overdue invoices, ageing, payment reminders, void invoice.
 */
class ReceivablesController extends Controller
{
    use RequiresAccountingPermission;

    public function __construct(private readonly ReceivablesService $receivables)
    {
    }

    public function overdue(Request $request)
    {
        $customerId = $request->integer('customer_id') ?: null;
        $rows = $this->receivables->overdue($customerId);
        $buckets = ['1–30 days', '31–60 days', '61–90 days', 'Over 90 days'];
        $totals = collect($buckets)->mapWithKeys(fn ($b) => [$b => round($rows->filter(fn ($o) => $o->ageingBucket() === $b)->sum(fn ($o) => (float) $o->due_amount), 2)]);
        $byCustomer = $rows->groupBy('customer_id');

        return view('accounting::phase6.receivables.overdue', compact('rows', 'buckets', 'totals', 'byCustomer'));
    }

    public function reminder(Order $order)
    {
        $this->requireAccountingAction('accounting.reminders.send');
        $order->loadMissing('customer');
        $result = $this->receivables->sendReminder($order);

        return back()->with($result['sent'] ? 'status' : 'error', $result['message'])
            ->withErrors($result['sent'] ? [] : ['reminder' => $result['message']]);
    }

    public function bulkReminders(Request $request)
    {
        $this->requireAccountingAction('accounting.reminders.send');
        $ids = array_map('intval', (array) $request->input('order_ids', []));
        $sent = 0;
        $failed = 0;
        foreach (Order::query()->with('customer')->whereIn('id', $ids)->get() as $order) {
            $this->receivables->sendReminder($order)['sent'] ? $sent++ : $failed++;
        }

        return back()->with('status', "Reminders sent: {$sent}" . ($failed ? " · not sent: {$failed}" : ''));
    }

    public function void(Request $request, Order $order)
    {
        $this->requireAccountingAction('accounting.invoices.void');
        $data = $request->validate(['reason' => 'required|string|max:255']);
        try {
            $this->receivables->voidInvoice($order, $data['reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }

        return back()->with('status', 'Invoice ' . $order->invoice_number . ' voided. Journals reversed and stock returned.');
    }
}

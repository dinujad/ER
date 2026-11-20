<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Order;
use App\Services\Accounting\ReceivablesService;
use App\Services\SalesAccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 6.4 — credit notes: list, raise against an invoice, PDF, void.
 */
class CreditNoteController extends Controller
{
    use RequiresAccountingPermission;

    public function __construct(private readonly ReceivablesService $receivables)
    {
    }

    public function index(Request $request)
    {
        $notes = Schema::hasTable('credit_notes')
            ? CreditNote::query()->with(['order', 'customer'])
                ->when($request->get('type'), fn ($q, $v) => $q->where('type', $v))
                ->orderByDesc('credit_note_date')->orderByDesc('id')->paginate(30)->withQueryString()
            : collect();

        return view('accounting::phase6.credit_notes.index', compact('notes'));
    }

    public function create(Request $request)
    {
        $this->requireAccountingAction('accounting.credit_notes.manage');
        $order = Order::query()->with(['customer', 'saleReturns', 'creditNotes'])->findOrFail((int) $request->get('order_id'));
        $creditable = $this->receivables->creditableAmount($order);
        $ratio = (float) $order->total_amount > 0 ? $order->invoice_total / (float) $order->total_amount : 1.0;

        return view('accounting::phase6.credit_notes.create', compact('order', 'creditable', 'ratio'));
    }

    public function store(Request $request)
    {
        $this->requireAccountingAction('accounting.credit_notes.manage');
        $data = $request->validate([
            'order_id' => 'required|integer',
            'goods_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'credit_note_date' => 'nullable|date',
        ]);
        $order = Order::query()->with('customer')->findOrFail($data['order_id']);

        try {
            $cn = $this->receivables->issueAdjustmentCreditNote($order, (float) $data['goods_amount'], $data['reason'], $data['credit_note_date'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['goods_amount' => $e->getMessage()]);
        }

        return redirect(url('accounting/phase6/invoices/' . $order->id) . '#credit-notes')
            ->with('status', 'Credit note ' . $cn->credit_note_number . ' issued for Rs ' . number_format($cn->total, 2) . '.');
    }

    public function pdf(int $id)
    {
        $cn = CreditNote::query()->with(['order.customer', 'saleReturn.items.product'])->findOrFail($id);
        $company = config('dermas.company', []);
        $pdf = Pdf::loadView('accounting::phase6.credit_notes.pdf', compact('cn', 'company'))->setPaper('a4', 'portrait');

        return $pdf->download('credit-note-' . $cn->credit_note_number . '.pdf');
    }

    public function void(Request $request, int $id)
    {
        $this->requireAccountingAction('accounting.credit_notes.manage');
        $data = $request->validate(['reason' => 'required|string|max:255']);
        $cn = CreditNote::query()->findOrFail($id);
        if ($cn->type !== CreditNote::TYPE_ADJUSTMENT) {
            return back()->withErrors(['reason' => 'Return credit notes follow their sale return — reverse the return instead.']);
        }
        $this->receivables->voidCreditNote($cn, $data['reason']);

        return back()->with('status', 'Credit note ' . $cn->credit_note_number . ' voided.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PreparesOrderChequePayment;
use App\Models\ChequeBatch;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesPersonChequeBatchController extends Controller
{
    use PreparesOrderChequePayment;

    public function index(): View
    {
        $batches = ChequeBatch::query()
            ->where('user_id', auth()->id())
            ->with(['payments.order.customer'])
            ->latest('id')
            ->paginate(25);

        return view('sales_person.multi-cheques.index', compact('batches'));
    }

    public function searchOrders(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $orders = Order::query()
            ->with('customer')
            ->visibleToSalesPerson()
            ->whereIn('status', [Order::STATUS_AFTERCHECKING, Order::STATUS_COMPLETED])
            ->where(function ($query) use ($q) {
                $query->where('order_number', 'like', '%'.$q.'%')
                    ->orWhere('invoice_number', 'like', '%'.$q.'%');
            })
            ->latest('id')
            ->limit(15)
            ->get()
            ->filter(fn (Order $order) => $order->due_amount > 0.009)
            ->take(8)
            ->values()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'business' => $order->customer?->display_name ?? '—',
                'due' => round((float) $order->due_amount, 2),
            ]);

        return response()->json($orders);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'cheque_number' => 'required|string|max:100',
            'cheque_bank' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.order_id' => 'required|integer|distinct|exists:orders,id',
            'lines.*.amount' => 'required|numeric|min:0.01',
        ]);

        $chequeFields = $this->prepareChequePaymentFieldsFromInput(
            $request->input('cheque_date'),
            $request->file('cheque_image'),
            $request->boolean('cheque_exception_requested'),
            (string) $request->input('cheque_exception_reason', ''),
            'cheque_date',
            'cheque_exception_reason'
        );
        if ($chequeFields instanceof RedirectResponse) {
            return $chequeFields;
        }

        $chequeAmount = round((float) $validated['amount'], 2);
        $lines = collect($validated['lines'])->map(fn ($line) => [
            'order_id' => (int) $line['order_id'],
            'amount' => round((float) $line['amount'], 2),
        ])->values();

        $allocated = round((float) $lines->sum('amount'), 2);
        if (abs($allocated - $chequeAmount) > 0.009) {
            return back()->withInput()->withErrors([
                'amount' => 'Order amounts (Rs '.number_format($allocated, 2).') must equal the cheque amount (Rs '.number_format($chequeAmount, 2).').',
            ]);
        }

        $orders = Order::query()
            ->with('customer')
            ->whereIn('id', $lines->pluck('order_id'))
            ->get()
            ->keyBy('id');

        foreach ($lines as $line) {
            $order = $orders->get($line['order_id']);
            if (!$order || !Order::visibleToSalesPerson()->whereKey($order->id)->exists()) {
                return back()->withInput()->withErrors([
                    'lines' => 'ඒ order එකක් ඔයාට add කරන්න බැහැ.',
                ]);
            }
            if (!$order->canAcceptPayments()) {
                return back()->withInput()->withErrors([
                    'lines' => $order->order_number.' එකට තාම payment දාන්න බැහැ. Order Check ඉවර වෙන්න ඕන.',
                ]);
            }
            if ($line['amount'] > $order->due_amount + 0.009) {
                return back()->withInput()->withErrors([
                    'lines' => $order->order_number.' due එක Rs '.number_format($order->due_amount, 2).'. ඊට වඩා දාන්න බැහැ.',
                ]);
            }
        }

        $batch = DB::transaction(function () use ($request, $validated, $chequeFields, $chequeAmount, $lines, $orders) {
            $batch = ChequeBatch::create([
                'batch_number' => 'TMP',
                'user_id' => auth()->id(),
                'amount' => $chequeAmount,
                'payment_date' => $validated['payment_date'],
                'cheque_date' => $chequeFields['cheque_date'],
                'cheque_number' => trim($validated['cheque_number']),
                'cheque_bank' => trim((string) ($validated['cheque_bank'] ?? '')) ?: null,
                'cheque_image_path' => $chequeFields['cheque_image_path'],
                'cheque_exception_requested' => $chequeFields['cheque_exception_requested'],
                'cheque_exception_reason' => $chequeFields['cheque_exception_reason'],
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]);
            $number = ChequeBatch::numberForId((int) $batch->id);
            $batch->update(['batch_number' => $number]);

            foreach ($lines as $line) {
                $order = $orders->get($line['order_id']);
                $order->payments()->create([
                    'cheque_batch_id' => $batch->id,
                    'entry_type' => Payment::ENTRY_PAYMENT,
                    'amount' => $line['amount'],
                    'settlement_amount' => $line['amount'],
                    'discount_amount' => 0,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => 'cheque',
                    'reference_number' => $batch->cheque_number,
                    'notes' => 'Multi cheque '.$number,
                    'cheque_date' => $chequeFields['cheque_date'],
                    'cheque_image_path' => $chequeFields['cheque_image_path'],
                    'cheque_exception_requested' => $chequeFields['cheque_exception_requested'],
                    'cheque_exception_reason' => $chequeFields['cheque_exception_reason'],
                    'cheque_status' => Payment::CHEQUE_RECEIVED,
                    'cheque_bank' => $batch->cheque_bank,
                    'cheque_number' => $batch->cheque_number,
                ]);
            }

            return $batch->fresh();
        });

        return redirect()
            ->route('sales-person.multi-cheques.index')
            ->with('success', $batch->batch_number.' saved. Cheque Rs '.number_format($chequeAmount, 2).' split across '.$lines->count().' orders.');
    }
}

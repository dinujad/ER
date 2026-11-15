<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\StockLedger;
use App\Http\Controllers\PosCartController;
use Illuminate\Http\Request;

class PosSaleController extends Controller
{
    /** Save current cart as draft (no stock deduction). */
    public function saveDraft(Request $request)
    {
        return $this->saveSale($request, Order::STATUS_DRAFT);
    }

    /** Save as suspended (parked). */
    public function saveSuspend(Request $request)
    {
        return $this->saveSale($request, 'suspended');
    }

    /** Save as quotation. */
    public function saveQuotation(Request $request)
    {
        return $this->saveSale($request, Order::STATUS_QUOTATION);
    }

    private function saveSale(Request $request, string $status)
    {
        $cart = PosCartController::getCart();
        $meta = PosCartController::getMeta();
        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }
        $totals = PosCartController::recalcTotals();
        $storeId = $request->get('store_id') ?: \App\Models\Store::orderBy('id')->value('id');
        $register = Register::where('store_id', $storeId)->where('status', 'open')->first()
            ?? Register::where('store_id', $storeId)->latest('id')->first();
        $isQuotation = $status === Order::STATUS_QUOTATION;
        $order = Order::create([
            'order_number' => $isQuotation ? Order::nextQuotationNumber() : Order::nextOrderNumber(),
            'store_id' => $storeId,
            'customer_id' => $meta['customer_id'] ?? Customer::where('phone', 'WALKIN')->value('id'),
            'user_id' => auth()->id(),
            'register_id' => $register?->id,
            'subtotal' => $totals['subtotal'],
            'total_amount' => $totals['total'],
            'discount_amount' => $totals['discount'],
            'discount_type' => $meta['discount_type'] ?? 'fixed',
            'discount_value' => $meta['discount_value'] ?? 0,
            'tax_type' => $meta['tax_type'] ?? 'percentage',
            'tax_value' => $meta['tax_value'] ?? 0,
            'shipping' => $totals['shipping'],
            'status' => $status,
            'order_date' => now(),
        ]);
        foreach ($cart as $row) {
            $order->items()->create([
                'product_id' => $row['product_id'],
                'product_variation_id' => $row['variation_id'] ?? null,
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'total_price' => $row['quantity'] * $row['unit_price'],
            ]);
        }
        PosCartController::clearCart();
        $payload = [
            'success' => true,
            'message' => $isQuotation
                ? 'Quotation ' . $order->order_number . ' saved under Retail Sales.'
                : 'Saved as ' . $status . '.',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];
        if ($isQuotation) {
            $payload['redirect'] = route('retail-quotations.show', $order);
        }

        return response()->json($payload);
    }

    /** Finalize sale: paid (cash/card/multiple) or credit. Stock deduction only for paid/credit. */
    public function finalize(Request $request)
    {
        $request->validate([
            'payment_type' => 'required|in:cash,card,multiple,credit',
            'store_id' => 'nullable|exists:stores,id',
            'amount_tendered' => 'nullable|numeric|min:0',
            'card_reference' => 'nullable|string|max:100',
            'card_note' => 'nullable|string|max:500',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|string|in:cash,card,mobile,other',
            'payments.*.amount' => 'required_with:payments|numeric|min:0',
        ]);
        $cart = PosCartController::getCart();
        $meta = PosCartController::getMeta();
        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }
        $totals = PosCartController::recalcTotals();
        $storeId = $request->store_id ?: \App\Models\Store::orderBy('id')->value('id');
        $customerId = $meta['customer_id'] ?? null;
        $walkInId = Customer::where('phone', 'WALKIN')->value('id');
        $customerId = $customerId ?: $walkInId;

        if ($request->payment_type === 'credit') {
            if (!$customerId || $customerId === $walkInId) {
                return response()->json(['success' => false, 'message' => 'Select a customer for credit sale.'], 422);
            }
            $customer = Customer::find($customerId);
            $newDue = ($customer->due_balance ?? 0) + $totals['total'];
            if (($customer->credit_limit ?? 0) > 0 && $newDue > $customer->credit_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit limit exceeded. Due would be ' . number_format($newDue, 2) . '.',
                ], 422);
            }
        }

        if ($request->payment_type === 'cash') {
            $register = Register::where('store_id', $storeId)->where('status', 'open')->first();
            if (!$register) {
                return response()->json(['success' => false, 'message' => 'Please open the cash register first.'], 422);
            }
        }

        $paymentRows = [];
        if ($request->payment_type === 'multiple' && $request->filled('payments')) {
            $sum = 0;
            foreach ($request->payments as $p) {
                $amt = (float) ($p['amount'] ?? 0);
                if ($amt > 0) {
                    $paymentRows[] = ['method' => $p['method'] ?? 'cash', 'amount' => $amt];
                    $sum += $amt;
                }
            }
            if ($sum < round($totals['total'], 2) - 0.01) {
                return response()->json(['success' => false, 'message' => 'Payment total must be at least the payable amount.'], 422);
            }
        } elseif ($request->payment_type === 'card') {
            $paymentRows[] = ['method' => 'card', 'amount' => $totals['total'], 'reference' => $request->card_reference, 'note' => $request->card_note];
        } elseif ($request->payment_type === 'cash') {
            $paymentRows[] = ['method' => 'cash', 'amount' => $totals['total']];
        } elseif ($request->payment_type === 'credit') {
            // no payment rows; status = credit
        }

        $register = null;
        if ($request->payment_type === 'cash' || ($request->payment_type === 'multiple' && in_array('cash', array_column($paymentRows, 'method')))) {
            $register = Register::where('store_id', $storeId)->where('status', 'open')->first();
        }

        $order = Order::create([
            'order_number' => Order::nextOrderNumber(),
            'invoice_number' => Order::nextInvoiceNumber(),
            'store_id' => $storeId,
            'customer_id' => $customerId,
            'user_id' => auth()->id(),
            'register_id' => $register?->id,
            'subtotal' => $totals['subtotal'],
            'total_amount' => $totals['total'],
            'discount_amount' => $totals['discount'],
            'discount_type' => $meta['discount_type'] ?? 'fixed',
            'discount_value' => $meta['discount_value'] ?? 0,
            'tax_type' => $meta['tax_type'] ?? 'percentage',
            'tax_value' => $meta['tax_value'] ?? 0,
            'shipping' => $totals['shipping'],
            'status' => $request->payment_type === 'credit' ? Order::STATUS_CREDIT : 'completed',
            'order_date' => now(),
        ]);

        foreach ($cart as $row) {
            $order->items()->create([
                'product_id' => $row['product_id'],
                'product_variation_id' => $row['variation_id'] ?? null,
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'total_price' => $row['quantity'] * $row['unit_price'],
            ]);
        }

        foreach ($paymentRows as $p) {
            $order->payments()->create([
                'amount' => $p['amount'],
                'payment_date' => now(),
                'payment_method' => $p['method'],
                'reference_number' => $p['reference'] ?? null,
                'notes' => $p['note'] ?? null,
            ]);
        }

        if ($request->payment_type === 'credit') {
            $customer = Customer::find($customerId);
            $customer->increment('due_balance', $totals['total']);
        }

        // Stock deduction for completed/credit
        foreach ($cart as $row) {
            $product = Product::find($row['product_id']);
            $variationId = $row['variation_id'] ?? null;
            $qty = (int) $row['quantity'];
            $payload = [
                'product_id' => $product->id,
                'channel' => 'retail',
                'transaction_type' => 'sale',
                'quantity' => -$qty,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => 'POS sale (retail stock)',
                'created_by' => auth()->id(),
            ];
            if ($variationId) {
                $payload['product_variation_id'] = $variationId;
            }
            StockLedger::create($payload);
        }

        PosCartController::clearCart();
        $amountTendered = $request->filled('amount_tendered') ? (float) $request->amount_tendered : null;
        $change = null;
        if ($amountTendered !== null && $amountTendered >= $totals['total']) {
            $change = round($amountTendered - $totals['total'], 2);
        }
        session()->flash('pos_amount_tendered', $amountTendered);
        session()->flash('pos_change', $change);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount_tendered' => $amountTendered,
            'change' => $change,
            'redirect' => route('pos.receipt', $order),
        ]);
    }

    public function recent(Request $request)
    {
        $query = Order::with('customer')->whereNotNull('order_number')
            ->whereIn('status', ['completed', 'paid', 'credit']);
        if ($request->get('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        $sales = $query->orderByDesc('created_at')->limit(20)->get();
        return response()->json([
            'sales' => $sales->map(fn ($o) => [
                'id' => $o->id,
                'invoice_no' => $o->invoice_number ?? $o->order_number,
                'customer' => $o->customer?->name ?? 'Walk-in',
                'total' => $o->total_amount,
                'payment_type' => $o->status === 'credit' ? 'credit' : ($o->payments->first()?->payment_method ?? 'cash'),
                'date' => $o->created_at->format('d M Y H:i'),
            ]),
        ]);
    }

    /** Load draft/suspended/quotation back into cart. */
    public function loadSale(Order $order)
    {
        if (!in_array($order->status, ['draft', 'suspended', 'quotation'])) {
            return response()->json(['success' => false, 'message' => 'This sale cannot be loaded.'], 422);
        }
        $cart = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            $cart[] = [
                'product_id' => $item->product_id,
                'variation_id' => $item->product_variation_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'product_name' => $product->name,
                'sku' => $product->sku ?? (string) $product->id,
                'variation_name' => $item->variation?->variation_name,
                'image' => $product->image,
            ];
        }
        PosCartController::setCart($cart);
        PosCartController::setMeta([
            'customer_id' => $order->customer_id,
            'discount_type' => $order->discount_type ?? 'fixed',
            'discount_value' => $order->discount_value ?? 0,
            'tax_type' => $order->tax_type ?? 'percentage',
            'tax_value' => $order->tax_value ?? 0,
            'shipping' => $order->shipping ?? 0,
        ]);
        return response()->json(['success' => true, 'cart' => $cart, 'totals' => PosCartController::recalcTotals()]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchasePaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,cheque,bank_transfer',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:100',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'cheque_bank' => 'required_if:payment_method,cheque|nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);
        $amount = (float) $request->amount;
        $isCheque = $request->payment_method === 'cheque';

        if ($request->purchase_id) {
            $purchase = Purchase::with('payments')->findOrFail($request->purchase_id);
            if ($purchase->supplier_id != $supplier->id) {
                return back()->with('error', 'Purchase does not belong to this supplier.');
            }
            if ($amount > $purchase->balance) {
                return back()->with('error', 'Amount cannot exceed purchase balance (' . number_format($purchase->balance, 2) . ').');
            }
        } else {
            $outstanding = $supplier->outstanding_balance;
            if ($amount > $outstanding) {
                return back()->with('error', 'Amount cannot exceed supplier outstanding (' . number_format($outstanding, 2) . ').');
            }
        }

        PurchasePayment::create([
            'supplier_id' => $supplier->id,
            'purchase_id' => $request->purchase_id,
            'entry_type' => PurchasePayment::ENTRY_PAYMENT,
            'amount' => $amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'reference' => $isCheque ? null : $request->reference,
            'cheque_number' => $isCheque ? $request->cheque_number : null,
            'cheque_date' => $isCheque ? $request->cheque_date : null,
            'cheque_bank' => $isCheque ? $request->cheque_bank : null,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        $msg = 'Payment recorded.';
        if ($request->purchase_id) {
            return redirect()->route('purchases.show', $request->purchase_id)->with('success', $msg);
        }
        return redirect()->route('suppliers.show', $supplier)->with('success', $msg);
    }

    public function storeRefund(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'required|exists:purchases,id',
            'purchase_return_id' => 'nullable|exists:purchase_returns,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,cheque,bank_transfer',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:100',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'cheque_bank' => 'required_if:payment_method,cheque|nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);
        $purchase = Purchase::with('payments')->findOrFail($request->purchase_id);
        $amount = (float) $request->amount;
        $isCheque = $request->payment_method === 'cheque';

        if ($purchase->supplier_id != $supplier->id) {
            return back()->with('error', 'Purchase does not belong to this supplier.');
        }
        if ($purchase->status !== Purchase::STATUS_CONFIRMED) {
            return back()->with('error', 'Refunds can only be recorded on confirmed purchases.');
        }
        if ($amount > $purchase->refundable_balance) {
            return back()->with('error', 'Amount cannot exceed refundable balance (' . number_format($purchase->refundable_balance, 2) . ').');
        }

        if ($request->purchase_return_id) {
            $return = $purchase->returns()->find($request->purchase_return_id);
            if (!$return) {
                return back()->with('error', 'Return does not belong to this purchase.');
            }
        }

        PurchasePayment::create([
            'supplier_id' => $supplier->id,
            'purchase_id' => $purchase->id,
            'purchase_return_id' => $request->purchase_return_id,
            'entry_type' => PurchasePayment::ENTRY_REFUND,
            'amount' => $amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'reference' => $isCheque ? null : $request->reference,
            'cheque_number' => $isCheque ? $request->cheque_number : null,
            'cheque_date' => $isCheque ? $request->cheque_date : null,
            'cheque_bank' => $isCheque ? $request->cheque_bank : null,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Refund recorded.');
    }
}

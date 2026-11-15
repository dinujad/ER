<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Register;
use App\Models\Store;
use Illuminate\Http\Request;

class PosRegisterController extends Controller
{
    /** GET current open register for the given store (or first store). */
    public function status(Request $request)
    {
        $storeId = $request->get('store_id');
        if (!$storeId && Store::exists()) {
            $storeId = Store::orderBy('id')->value('id');
        }
        $register = null;
        if ($storeId) {
            $register = Register::where('store_id', $storeId)->where('status', 'open')->first();
        }
        return response()->json([
            'open' => $register !== null,
            'register' => $register ? [
                'id' => $register->id,
                'opening_cash' => $register->opening_cash,
                'opened_at' => $register->opened_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'opening_cash' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);
        $open = Register::where('store_id', $request->store_id)->where('status', 'open')->first();
        if ($open) {
            return response()->json(['success' => false, 'message' => 'A register is already open for this store.'], 422);
        }
        $register = Register::create([
            'store_id' => $request->store_id,
            'opening_cash' => $request->opening_cash,
            'status' => 'open',
            'opened_at' => now(),
            'opening_note' => $request->note,
            'opened_by' => auth()->id(),
        ]);
        return response()->json(['success' => true, 'register' => $register]);
    }

    public function close(Request $request)
    {
        $request->validate([
            'register_id' => 'required|exists:registers,id',
            'counted_cash' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);
        $register = Register::find($request->register_id);
        if ($register->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'Register is not open.'], 422);
        }
        $cashSales = Order::where('register_id', $register->id)
            ->whereIn('status', ['paid', 'completed'])
            ->whereHas('payments', fn ($q) => $q->where('payment_method', 'cash'))
            ->get()
            ->sum(fn ($o) => $o->payments()->where('payment_method', 'cash')->sum('amount'));
        $cardSales = Order::where('register_id', $register->id)
            ->whereIn('status', ['paid', 'completed'])
            ->get()
            ->sum(fn ($o) => $o->payments()->whereIn('payment_method', ['card', 'mobile', 'other'])->sum('amount'));
        $creditSales = Order::where('register_id', $register->id)->where('status', 'credit')->sum('total_amount');
        $cashExpenses = $register->expenses()->where('payment_method', 'cash')->sum('amount');
        $expectedCash = (float) $register->opening_cash + $cashSales - $cashExpenses;
        $countedCash = (float) $request->counted_cash;
        $difference = $countedCash - $expectedCash;
        $register->update([
            'closing_cash' => $countedCash,
            'expected_cash' => $expectedCash,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
            'closing_note' => $request->note,
            'closed_by' => auth()->id(),
        ]);
        return response()->json([
            'success' => true,
            'summary' => [
                'cash_sales' => round($cashSales, 2),
                'card_sales' => round($cardSales, 2),
                'credit_sales' => round($creditSales, 2),
                'cash_expenses' => round($cashExpenses, 2),
                'expected_cash' => round($expectedCash, 2),
                'counted_cash' => $countedCash,
                'difference' => round($difference, 2),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Register;
use Illuminate\Http\Request;

class PosExpenseController extends Controller
{
    /** Quick add expense from POS (AJAX). */
    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card',
            'note' => 'nullable|string|max:500',
            'store_id' => 'nullable|exists:stores,id',
        ]);
        $storeId = $request->store_id ?: \App\Models\Store::orderBy('id')->value('id');
        $registerId = null;
        if ($request->payment_method === 'cash') {
            $register = Register::where('store_id', $storeId)->where('status', 'open')->first();
            if (!$register) {
                return response()->json(['success' => false, 'message' => 'Open the cash register first for cash expenses.'], 422);
            }
            $registerId = $register->id;
        }
        Expense::create([
            'expense_category_id' => $request->expense_category_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'expense_date' => now(),
            'note' => $request->note,
            'created_by' => auth()->id(),
            'register_id' => $registerId,
        ]);
        return response()->json(['success' => true, 'message' => 'Expense added.']);
    }
}

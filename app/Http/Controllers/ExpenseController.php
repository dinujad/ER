<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\CostReportService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('category', 'creator');

        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }
        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $categoriesByTopic = app(CostReportService::class)->groupCategoriesByTopic($categories);

        return view('erp.expenses.index', compact('expenses', 'categories', 'categoriesByTopic'));
    }

    public function create()
    {
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $categoriesByTopic = app(CostReportService::class)->groupCategoriesByTopic($categories);

        return view('erp.expenses.create', compact('categories', 'categoriesByTopic'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);
        Expense::create([
            'expense_category_id' => $request->expense_category_id,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'reference' => $request->reference,
            'note' => $request->note,
            'created_by' => auth()->id(),
        ]);
        return redirect()->route('expenses.index')->with('success', 'Expense added.');
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $categoriesByTopic = app(CostReportService::class)->groupCategoriesByTopic($categories);

        return view('erp.expenses.edit', compact('expense', 'categories', 'categoriesByTopic'));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);
        $expense->update($request->only('expense_category_id', 'amount', 'expense_date', 'reference', 'note'));
        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Services\CostReportService;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();
        $categoriesByTopic = app(CostReportService::class)->groupCategoriesByTopic($categories);
        $topics = app(CostReportService::class)->topics();

        return view('erp.expenses.categories.index', compact('categoriesByTopic', 'topics'));
    }

    public function create()
    {
        return view('erp.expenses.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:expense_categories,code',
            'description' => 'nullable|string',
        ]);
        ExpenseCategory::create($request->only('name', 'code', 'description'));
        return redirect()->route('expense-categories.index')->with('success', 'Expense category created.');
    }

    public function edit(ExpenseCategory $expense_category)
    {
        return view('erp.expenses.categories.edit', ['category' => $expense_category]);
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:expense_categories,code,' . $expense_category->id,
            'description' => 'nullable|string',
        ]);
        $expense_category->update($request->only('name', 'code', 'description'));
        return redirect()->route('expense-categories.index')->with('success', 'Expense category updated.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        if ($expense_category->expenses()->exists()) {
            return back()->with('error', 'Cannot delete category with expenses.');
        }
        $expense_category->delete();
        return redirect()->route('expense-categories.index')->with('success', 'Expense category deleted.');
    }
}

<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;

/**
 * ERP ledger: shows auto-posted journal lines for sales, purchases, expenses, and inventory/mfg.
 * Legacy Ultimate POS `transactions` mapping is disabled — books live in journal_entries.
 */
class AccountingTransactionController extends Controller
{
    /** @var array<string, list<string>> */
    public const MODULE_TYPES = [
        'sales' => [
            'sale',
            'sale_cogs',
            'customer_payment',
            'customer_refund',
            'sale_return',
            'sale_return_cogs',
            'cheque_receipt',
            'cheque_clearance',
        ],
        'purchases' => [
            'purchase',
            'purchase_payment',
            'purchase_return',
            'supplier_refund',
        ],
        'expenses' => [
            'expense',
            'sales_person_expense',
            'vehicle_run_expense',
            'payroll_accrual',
            'payroll_payment',
        ],
        'inventory' => [
            'manufacturing_material_issue',
            'manufacturing_additional_cost',
            'manufacturing_finished_goods',
            'manufacturing_overhead_allocation',
            'material_inventory_adjustment',
            'finished_goods_adjustment',
        ],
    ];

    public function sales(Request $request)
    {
        return $this->moduleLedger($request, 'sales');
    }

    public function expenses(Request $request)
    {
        return $this->moduleLedger($request, 'expenses');
    }

    public function purchases(Request $request)
    {
        return $this->moduleLedger($request, 'purchases');
    }

    public function inventory(Request $request)
    {
        return $this->moduleLedger($request, 'inventory');
    }

    public function map_to_chart_of_account(Request $request)
    {
        abort(410, 'Legacy single-sided transaction mapping is disabled. ERP transactions post balanced journals automatically.');
    }

    protected function moduleLedger(Request $request, string $module)
    {
        $types = self::MODULE_TYPES[$module] ?? [];
        $businessId = session('business.id');
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $search = trim((string) $request->s);
        $typeFilter = $request->transaction_type;

        $coaReady = Schema::hasTable('chart_of_accounts')
            && ChartOfAccount::query()->where('active', 1)->exists();

        $query = JournalEntry::query()
            ->leftJoin('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where(function ($q) use ($businessId) {
                $q->where('chart_of_accounts.business_id', $businessId)
                    ->orWhere('chart_of_accounts.business_id', 0)
                    ->orWhereNull('chart_of_accounts.business_id');
            })
            ->where(function ($q) {
                $q->where('journal_entries.reversed', 0)->orWhereNull('journal_entries.reversed');
            })
            ->whereIn('journal_entries.transaction_type', $types)
            ->when($typeFilter && in_array($typeFilter, $types, true), function (Builder $q) use ($typeFilter) {
                $q->where('journal_entries.transaction_type', $typeFilter);
            })
            ->when($startDate && $endDate, function (Builder $q) use ($startDate, $endDate) {
                $q->whereBetween('journal_entries.date', [$startDate, $endDate]);
            })
            ->when($startDate && ! $endDate, function (Builder $q) use ($startDate) {
                $q->where('journal_entries.date', '>=', $startDate);
            })
            ->when($endDate && ! $startDate, function (Builder $q) use ($endDate) {
                $q->where('journal_entries.date', '<=', $endDate);
            })
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('journal_entries.transaction_number', 'like', "%{$search}%")
                        ->orWhere('journal_entries.name', 'like', "%{$search}%")
                        ->orWhere('journal_entries.reference', 'like', "%{$search}%")
                        ->orWhere('journal_entries.transaction_type', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('journal_entries.date')
            ->orderByDesc('journal_entries.id')
            ->selectRaw("
                journal_entries.id,
                journal_entries.transaction_number,
                journal_entries.transaction_type,
                journal_entries.name,
                journal_entries.reference,
                journal_entries.date,
                journal_entries.debit,
                journal_entries.credit,
                journal_entries.source_type,
                journal_entries.source_id,
                chart_of_accounts.name as account_name,
                chart_of_accounts.gl_code,
                chart_of_accounts.account_type
            ");

        $data = $query->limit(500)->get();

        $counts = [];
        foreach (array_keys(self::MODULE_TYPES) as $key) {
            $counts[$key] = JournalEntry::query()
                ->where(function ($q) {
                    $q->where('reversed', 0)->orWhereNull('reversed');
                })
                ->whereIn('transaction_type', self::MODULE_TYPES[$key])
                ->count();
        }

        $titles = [
            'sales' => 'Sales transactions',
            'purchases' => 'Purchase transactions',
            'expenses' => 'Expense transactions',
            'inventory' => 'Inventory & manufacturing transactions',
        ];

        $descriptions = [
            'sales' => 'Orders, customer payments, cheques, and sale returns posted from ERP',
            'purchases' => 'Supplier purchases, payments, and purchase returns posted from ERP',
            'expenses' => 'Office expenses, field expenses, vehicle runs, and payroll posted from ERP',
            'inventory' => 'Material issues, stock adjustments, finished goods, and manufacturing costs posted from ERP',
        ];

        return view('accounting::transactions.erp_ledger', [
            'module' => $module,
            'title' => $titles[$module] ?? 'ERP transactions',
            'description' => $descriptions[$module] ?? '',
            'types' => $types,
            'data' => $data,
            'counts' => $counts,
            'coaReady' => $coaReady,
            'typeFilter' => $typeFilter,
        ]);
    }
}

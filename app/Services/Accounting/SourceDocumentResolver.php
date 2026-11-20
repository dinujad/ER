<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * "Where did this come from?" — maps a journal / audit source (type + id) to a human label and a URL
 * of the document that created it, so any report figure can be drilled down to its source (Xero-style).
 */
class SourceDocumentResolver
{
    public static function forJournal(object $line): array
    {
        $type = $line->source_type ?? null;
        $id = $line->source_id ?? null;
        if (!$type || !$id) {
            $reference = trim((string) ($line->reference ?? ''));
            [$table, $columns, $fallbackType, $value] = match ($line->transaction_type ?? null) {
                'sale', 'sale_cogs' => ['orders', ['invoice_number', 'order_number'], 'order', $reference],
                'purchase' => ['purchases', ['purchase_no'], 'purchase', $reference],
                'expense' => ['expenses', ['accounting_txn_number'], 'expense', $line->transaction_number ?? ''],
                default => [null, [], null, ''],
            };
            if ($table && $value !== '' && \App\Support\SchemaCache::hasTable($table)) {
                $columns = array_values(array_filter($columns, fn ($column) => \App\Support\SchemaCache::hasColumn($table, $column)));
                if ($columns !== []) {
                    $matches = DB::table($table)->where(function ($query) use ($columns, $value) {
                        foreach ($columns as $column) {
                            $query->orWhere($column, $value);
                        }
                    })->limit(2)->pluck('id');
                    if ($matches->count() === 1) {
                        $type = $fallbackType;
                        $id = $matches->first();
                    }
                }
            }
        }

        return self::resolve($type, $id);
    }

    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * @return array{label: string, url: ?string, type: string, details?: array}
     */
    public static function resolve(?string $type, mixed $id): array
    {
        $type = (string) $type;
        $id = is_numeric($id) ? (int) $id : null;
        $key = $type . '#' . $id;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $short = self::shortType($type);
        $result = ['label' => self::typeLabel($short) . ($id ? ' #' . $id : ''), 'url' => null, 'type' => $short];

        try {
            $result = array_merge($result, self::lookup($short, $id));
        } catch (\Throwable) {
            // keep generic label
        }

        return self::$cache[$key] = $result;
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }

    public static function shortType(string $type): string
    {
        $base = class_basename(str_replace('/', '\\', $type));

        return match (strtolower($base)) {
            'order' => 'order',
            'payment' => 'payment',
            'salereturn' => 'sale_return',
            'purchase' => 'purchase',
            'purchasepayment' => 'purchase_payment',
            'purchasereturn' => 'purchase_return',
            'expense' => 'expense',
            'salespersonexpense' => 'field_expense',
            'vehicledailyrunitem' => 'vehicle_expense',
            'materialissue' => 'material_issue',
            'manufacturingjobadditionalcost' => 'job_additional_cost',
            'materialstockmovement' => 'material_movement',
            'stockledger' => 'stock_movement',
            'customer' => 'customer',
            'supplier' => 'supplier',
            'product' => 'product',
            'payrollrunitem' => 'payroll',
            'manual_journal', 'manualjournal' => 'manual_journal',
            'creditnote' => 'credit_note',
            default => strtolower($base),
        };
    }

    public static function typeLabel(string $short): string
    {
        return match ($short) {
            'order' => 'Invoice',
            'payment' => 'Customer payment',
            'sale_return' => 'Sale return',
            'purchase' => 'Bill',
            'purchase_payment' => 'Supplier payment',
            'purchase_return' => 'Purchase return',
            'expense' => 'Expense',
            'field_expense' => 'Field expense',
            'vehicle_expense' => 'Vehicle expense',
            'job_additional_cost' => 'Manufacturing additional cost',
            'mfg_overhead_run_line' => 'Manufacturing overhead allocation',
            'manual_journal' => 'Manual journal',
            'credit_note' => 'Credit note',
            'payroll' => 'Payroll',
            'fa_asset', 'fa_depreciation', 'fa_disposal', 'fa_capitalization_candidate' => 'Fixed asset',
            'acct_loan' => 'Loan',
            default => ucwords(str_replace('_', ' ', $short)),
        };
    }

    /**
     * @return array{label?: string, url?: ?string, details?: array}
     */
    private static function lookup(string $short, ?int $id): array
    {
        if (!$id) {
            return [];
        }

        switch ($short) {
            case 'order':
                $o = self::row('orders', $id, ['invoice_number', 'order_number']);
                return [
                    'label' => $o && $o->invoice_number ? 'Invoice ' . $o->invoice_number : 'Order ' . ($o->order_number ?? '#' . $id),
                    'url' => $o ? url('accounting/phase6/invoices/' . $id) : null,
                ];
            case 'payment':
                $p = self::row('payments', $id, ['order_id']);
                return [
                    'label' => 'Customer payment #' . $id,
                    'url' => $p && $p->order_id ? url('accounting/phase6/invoices/' . $p->order_id) . '#payments' : null,
                ];
            case 'sale_return':
                $r = self::row('sale_returns', $id, ['order_id', 'return_no']);
                return [
                    'label' => 'Sale return ' . ($r->return_no ?? '#' . $id),
                    'url' => $r && $r->order_id ? url('accounting/phase6/invoices/' . $r->order_id) . '#returns' : self::route('sale-returns.show', $id),
                ];
            case 'credit_note':
                $c = self::row('credit_notes', $id, ['order_id', 'credit_note_number']);
                return [
                    'label' => 'Credit note ' . ($c->credit_note_number ?? '#' . $id),
                    'url' => $c && $c->order_id ? url('accounting/phase6/invoices/' . $c->order_id) . '#credit-notes' : null,
                ];
            case 'purchase':
                $b = self::row('purchases', $id, ['purchase_no']);
                return ['label' => 'Bill ' . ($b->purchase_no ?? '#' . $id), 'url' => $b ? url('accounting/phase6/bills/' . $id) : null];
            case 'purchase_payment':
                $pp = self::row('purchase_payments', $id, ['purchase_id', 'supplier_id']);
                return [
                    'label' => 'Supplier payment #' . $id,
                    'url' => $pp && $pp->purchase_id
                        ? url('accounting/phase6/bills/' . $pp->purchase_id) . '#payments'
                        : ($pp && $pp->supplier_id ? self::route('suppliers.show', $pp->supplier_id) : null),
                ];
            case 'purchase_return':
                $pr = self::row('purchase_returns', $id, ['purchase_id', 'return_no']);
                return [
                    'label' => 'Purchase return ' . ($pr->return_no ?? '#' . $id),
                    'url' => $pr && $pr->purchase_id ? url('accounting/phase6/bills/' . $pr->purchase_id) . '#returns' : null,
                ];
            case 'expense':
                $expense = self::row('expenses', $id, ['reference', 'expense_category_id', 'expense_date', 'amount', 'payment_method', 'note']);
                $category = !empty($expense->expense_category_id) ? self::row('expense_categories', $expense->expense_category_id, ['name']) : null;
                return [
                    'label' => 'Expense #' . $id . (!empty($expense->reference) ? ' · ' . $expense->reference : ''),
                    'url' => $expense ? self::route('expenses.edit', $id) : null,
                    'details' => [
                        'Category' => $category->name ?? null,
                        'Expense date' => $expense->expense_date ?? null,
                        'Amount' => isset($expense->amount) ? 'LKR ' . number_format((float) $expense->amount, 2) : null,
                        'Payment method' => $expense->payment_method ?? null,
                        'Note' => $expense->note ?? null,
                    ],
                ];
            case 'field_expense':
                $expense = self::row('sales_person_expenses', $id, ['slip_path', 'type', 'description', 'amount']);
                return [
                    'url' => !empty($expense->slip_path) ? self::route('field-expenses.sales-person.receipt', $id) : null,
                    'details' => [
                        'Category' => $expense->type ?? null,
                        'Description' => $expense->description ?? null,
                        'Amount' => isset($expense->amount) ? 'LKR ' . number_format((float) $expense->amount, 2) : null,
                    ],
                ];
            case 'vehicle_expense':
                $expense = self::row('vehicle_daily_run_items', $id, ['slip_path', 'type', 'name', 'cost']);
                return [
                    'url' => !empty($expense->slip_path) ? self::route('field-expenses.driver.receipt', $id) : null,
                    'details' => [
                        'Category' => $expense->type ?? null,
                        'Description' => $expense->name ?? null,
                        'Amount' => isset($expense->cost) ? 'LKR ' . number_format((float) $expense->cost, 2) : null,
                    ],
                ];
            case 'payroll':
                $item = self::row('payroll_run_items', $id, ['payroll_run_id']);
                return ['url' => !empty($item->payroll_run_id) ? self::route('payroll-runs.show', $item->payroll_run_id) : null];
            case 'material_issue':
            case 'job_additional_cost':
                $table = $short === 'material_issue' ? 'material_issues' : 'manufacturing_job_additional_costs';
                $record = self::row($table, $id, ['manufacturing_job_id']);
                return ['url' => !empty($record->manufacturing_job_id) ? url('accounting/job-costs/' . $record->manufacturing_job_id) : null];
            case 'stock_movement':
            case 'material_movement':
                $table = $short === 'stock_movement' ? 'stock_ledgers' : 'material_stock_movements';
                $movement = self::row($table, $id, ['reference_type', 'reference_id']);
                return ['url' => class_basename($movement->reference_type ?? '') === 'ManufacturingJob' && !empty($movement->reference_id)
                    ? url('accounting/job-costs/' . $movement->reference_id) : null];
            case 'mfg_overhead_run_line':
                $line = self::row('mfg_overhead_run_lines', $id, ['job_id']);
                return ['url' => !empty($line->job_id) ? url('accounting/job-costs/' . $line->job_id) : null];
            case 'customer':
                return ['url' => self::route('erp.customers.show', $id)];
            case 'supplier':
                return ['url' => self::route('suppliers.show', $id)];
            case 'product':
                return ['url' => self::route('products.edit', $id)];
            case 'manual_journal':
                return ['url' => url('accounting/manual-journals/' . $id)];
            case 'journal_recode':
            case 'journal_entry':
                $line = self::row('journal_entries', $id, ['transaction_number']);
                return [
                    'label' => ($short === 'journal_recode' ? 'Recode of ' : '') . 'journal ' . ($line->transaction_number ?? 'line #' . $id),
                    'url' => $line ? url('accounting/phase6/journal/' . urlencode($line->transaction_number)) : null,
                ];
            case 'fa_asset':
                return ['url' => url('accounting/fixed-assets/' . $id)];
            case 'acct_loan':
                return ['url' => url('accounting/loans/' . $id)];
        }

        return [];
    }

    private static function row(string $table, int $id, array $columns): ?object
    {
        if (!\App\Support\SchemaCache::hasTable($table)) {
            return null;
        }
        $columns = array_values(array_filter($columns, fn ($c) => \App\Support\SchemaCache::hasColumn($table, $c)));

        return DB::table($table)->where('id', $id)->first($columns === [] ? ['id'] : $columns);
    }

    private static function route(string $name, int $id): ?string
    {
        return Route::has($name) ? route($name, $id) : null;
    }
}

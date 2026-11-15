<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\MaterialIssue;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\SalesPersonExpense;
use App\Models\VehicleDailyRunItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Unified expense ledger — every cost/outflow line from across the ERP + accounting link status.
 */
class ExpenseLedgerService
{
    /** @var array<string, string> */
    public const SOURCE_OPTIONS = [
        'all' => 'All sources',
        'expense' => 'ERP Expenses',
        'sales_field' => 'Sales person expenses',
        'purchase_payment' => 'Supplier / purchase payments',
        'vehicle' => 'Vehicle / driver runs',
        'payroll' => 'Payroll (salaries)',
        'job_cost' => 'Production added costs',
        'material' => 'Material to production',
    ];

    public function __construct(
        private readonly CostReportService $costReport
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function collect(string $from, string $to, string $sourceFilter = 'all'): Collection
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $rows = collect();

        if ($this->includeSource($sourceFilter, 'expense')) {
            $rows = $rows->merge($this->ledgerFromExpenses($from, $to));
        }
        if ($this->includeSource($sourceFilter, 'sales_field')) {
            $rows = $rows->merge($this->ledgerFromSalesPersonExpenses($from, $to));
        }
        if ($this->includeSource($sourceFilter, 'purchase_payment')) {
            $rows = $rows->merge($this->ledgerFromPurchasePayments($from, $to));
        }
        if ($this->includeSource($sourceFilter, 'vehicle')) {
            $rows = $rows->merge($this->ledgerFromVehicleRuns($from, $to));
        }
        if ($this->includeSource($sourceFilter, 'payroll')) {
            $rows = $rows->merge($this->ledgerFromPayroll($from, $to));
        }
        if ($this->includeSource($sourceFilter, 'job_cost')) {
            $rows = $rows->merge($this->ledgerFromJobCosts($from, $to));
        }
        if ($this->includeSource($sourceFilter, 'material')) {
            $rows = $rows->merge($this->ledgerFromMaterialIssues($from, $to));
        }

        return $this->enrichAccountingStatus(
            $rows->filter(fn (array $row) => abs((float) ($row['amount'] ?? 0)) > 0)
        );
    }

    public static function accountingEventKey(string $sourceKey, int $sourceId): ?string
    {
        return match ($sourceKey) {
            'expense' => "expense:{$sourceId}:entry",
            'sales_field' => "sales-person-expense:{$sourceId}:entry",
            'purchase_payment' => "purchase-payment:{$sourceId}:entry",
            'vehicle' => "vehicle-run-item:{$sourceId}:entry",
            'payroll' => "payroll-item:{$sourceId}:accrual",
            'job_cost' => "inventory:manufacturing-additional-cost:{$sourceId}",
            'material' => "inventory:material-issue:{$sourceId}",
            default => null,
        };
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    public function enrichAccountingStatus(Collection $rows): Collection
    {
        $keys = $rows->pluck('accounting_event_key')->filter()->unique()->values()->all();
        if ($keys === []) {
            return $rows;
        }

        $postings = DB::table('accounting_postings')
            ->whereIn('event_key', $keys)
            ->where('reversed', false)
            ->pluck('transaction_number', 'event_key');

        return $rows->map(function (array $row) use ($postings) {
            $key = $row['accounting_event_key'] ?? null;
            $txn = $key ? ($postings[$key] ?? null) : null;
            $row['accounting_txn'] = $txn;
            $row['accounting_linked'] = $txn !== null && $txn !== 'PENDING';

            return $row;
        });
    }

    private function includeSource(string $filter, string $key): bool
    {
        return $filter === 'all' || $filter === '' || $filter === $key;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromExpenses(string $from, string $to): Collection
    {
        return Expense::query()
            ->with('category', 'creator')
            ->whereBetween('expense_date', [$from, $to])
            ->orderBy('expense_date')
            ->get()
            ->map(function (Expense $expense) {
                $code = strtoupper((string) ($expense->category?->code ?? ''));
                $name = (string) ($expense->category?->name ?? 'Uncategorized');
                $topic = $this->costReport->resolveExpenseTopic($code, $name);

                return $this->row(
                    $expense->id,
                    Expense::class,
                    'expense',
                    $expense->expense_date?->format('Y-m-d') ?? '',
                    'ERP Expenses',
                    $name,
                    $topic,
                    trim(($expense->note ?: $expense->reference) ?: '—'),
                    $expense->reference,
                    (float) $expense->amount,
                    $expense->payment_method,
                    $expense->creator?->name
                );
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromSalesPersonExpenses(string $from, string $to): Collection
    {
        return SalesPersonExpense::query()
            ->with('user')
            ->whereBetween('expense_date', [$from, $to])
            ->orderBy('expense_date')
            ->get()
            ->map(function (SalesPersonExpense $expense) {
                $label = $expense->typeLabel();
                $topic = match ($expense->type) {
                    SalesPersonExpense::TYPE_FUEL, SalesPersonExpense::TYPE_TRAVEL => 'packing_logistics',
                    SalesPersonExpense::TYPE_SUPPLIES => 'packing_logistics',
                    default => 'admin_overheads',
                };

                return $this->row(
                    $expense->id,
                    SalesPersonExpense::class,
                    'sales_field',
                    $expense->expense_date?->format('Y-m-d') ?? '',
                    'Sales person expense',
                    $label,
                    $topic,
                    trim($expense->description ?: ($expense->notes ?? '—')),
                    $expense->user?->name,
                    (float) $expense->amount,
                    null,
                    $expense->user?->name
                );
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromPurchasePayments(string $from, string $to): Collection
    {
        $fromRows = PurchasePayment::query()
            ->with(['supplier', 'purchase', 'creator'])
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->get()
            ->map(function (PurchasePayment $payment) {
                $signed = $payment->isRefund()
                    ? -abs((float) $payment->amount)
                    : (float) $payment->amount;

                return $this->row(
                    $payment->id,
                    PurchasePayment::class,
                    'purchase_payment',
                    $payment->payment_date?->format('Y-m-d') ?? '',
                    $payment->isRefund() ? 'Supplier refund' : 'Purchase payment',
                    $payment->supplier?->name ?? 'Supplier',
                    'direct',
                    trim(($payment->purchase?->purchase_no ? 'PO ' . $payment->purchase->purchase_no . ' — ' : '') . ($payment->notes ?? '')),
                    $payment->reference ?? $payment->purchase?->purchase_no,
                    $signed,
                    $payment->payment_method,
                    $payment->creator?->name
                );
            });

        $legacy = Purchase::query()
            ->with(['supplier', 'payments', 'creator'])
            ->where('status', Purchase::STATUS_CONFIRMED)
            ->where('paid_amount', '>', 0)
            ->whereBetween('date', [$from, $to])
            ->get()
            ->filter(function (Purchase $purchase) {
                $fromPayments = $purchase->paymentEntriesTotal();
                $header = (float) $purchase->paid_amount;

                return $header > 0 && abs($header - $fromPayments) > 0.009;
            })
            ->map(function (Purchase $purchase) {
                $row = $this->row(
                    $purchase->id,
                    Purchase::class,
                    'purchase_payment',
                    $purchase->date?->format('Y-m-d') ?? '',
                    'Purchase payment',
                    $purchase->supplier?->name ?? 'Supplier',
                    'direct',
                    'PO ' . ($purchase->purchase_no ?? $purchase->id) . ' (header payment)',
                    $purchase->payment_reference ?? $purchase->purchase_no,
                    (float) $purchase->paid_amount,
                    $purchase->payment_method,
                    $purchase->creator?->name
                );
                $row['accounting_event_key'] = "purchase:{$purchase->id}:initial-payment";

                return $row;
            });

        return $fromRows->concat($legacy)->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromVehicleRuns(string $from, string $to): Collection
    {
        return VehicleDailyRunItem::query()
            ->with(['run.vehicle', 'run.driver'])
            ->whereHas('run', fn ($q) => $q->whereDate('run_date', '>=', $from)->whereDate('run_date', '<=', $to))
            ->get()
            ->map(function (VehicleDailyRunItem $item) {
                $run = $item->run;
                $vehicle = $run?->vehicle?->number_plate ?? $run?->vehicle?->name ?? 'Vehicle';

                return $this->row(
                    $item->id,
                    VehicleDailyRunItem::class,
                    'vehicle',
                    $run?->run_date?->format('Y-m-d') ?? '',
                    'Vehicle / driver run',
                    ucfirst((string) $item->type),
                    'packing_logistics',
                    trim(($item->name ?: $vehicle) . ($run?->notes ? ' — ' . $run->notes : '')),
                    $vehicle,
                    (float) $item->cost,
                    null,
                    $run?->driver?->name
                );
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromPayroll(string $from, string $to): Collection
    {
        return PayrollRunItem::query()
            ->with(['payrollRun', 'employee'])
            ->whereHas('payrollRun', function ($q) use ($from, $to) {
                $q->where('status', PayrollRun::STATUS_FINALIZED)
                    ->where(function ($period) use ($from, $to) {
                        $period->whereBetween('period_start', [$from, $to])
                            ->orWhereBetween('period_end', [$from, $to])
                            ->orWhere(function ($spans) use ($from, $to) {
                                $spans->where('period_start', '<=', $from)->where('period_end', '>=', $to);
                            });
                    });
            })
            ->get()
            ->map(function (PayrollRunItem $item) {
                $run = $item->payrollRun;

                return $this->row(
                    $item->id,
                    PayrollRunItem::class,
                    'payroll',
                    $run?->period_end?->format('Y-m-d') ?? $run?->finalized_at?->format('Y-m-d') ?? '',
                    'Payroll',
                    $run?->title ?? 'Payroll run',
                    'indirect_labor',
                    $item->employee?->name ?? 'Employee',
                    $item->reference_no,
                    (float) $item->net_salary,
                    $item->payment_status,
                    null
                );
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromJobCosts(string $from, string $to): Collection
    {
        return ManufacturingJobAdditionalCost::query()
            ->with(['job', 'creator'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderBy('created_at')
            ->get()
            ->map(function (ManufacturingJobAdditionalCost $cost) {
                $topic = match ($cost->category) {
                    ManufacturingJobAdditionalCost::CAT_SEWING,
                    ManufacturingJobAdditionalCost::CAT_LABOUR,
                    ManufacturingJobAdditionalCost::CAT_EMBROIDERY,
                    ManufacturingJobAdditionalCost::CAT_PRINTING,
                    ManufacturingJobAdditionalCost::CAT_WASHING => 'direct',
                    ManufacturingJobAdditionalCost::CAT_TRANSPORT => 'packing_logistics',
                    default => 'admin_overheads',
                };

                return $this->row(
                    $cost->id,
                    ManufacturingJobAdditionalCost::class,
                    'job_cost',
                    $cost->created_at?->format('Y-m-d') ?? '',
                    'Production added cost',
                    $cost->categoryLabel(),
                    $topic,
                    trim($cost->description ?: '—'),
                    $cost->job?->job_number,
                    (float) $cost->amount,
                    null,
                    $cost->creator?->name
                );
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function ledgerFromMaterialIssues(string $from, string $to): Collection
    {
        return MaterialIssue::query()
            ->with(['material', 'job'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereNotNull('manufacturing_job_id')
            ->orderBy('created_at')
            ->get()
            ->map(function (MaterialIssue $issue) {
                $amount = $issue->line_cost !== null
                    ? abs((float) $issue->line_cost)
                    : round(abs((float) $issue->quantity_issued) * $issue->effectiveUnitCost(), 2);

                if ($amount <= 0) {
                    return null;
                }

                return $this->row(
                    $issue->id,
                    MaterialIssue::class,
                    'material',
                    $issue->created_at?->format('Y-m-d') ?? '',
                    'Material to production',
                    $issue->material?->name ?? 'Material',
                    'direct',
                    'Issued to job ' . ($issue->job?->job_number ?? '—'),
                    $issue->job?->job_number,
                    $amount,
                    null,
                    $issue->issued_to
                );
            })
            ->filter()
            ->values();
    }

    /** @return array<string, mixed> */
    private function row(
        int $sourceId,
        string $sourceType,
        string $sourceKey,
        string $date,
        string $source,
        string $category,
        string $topic,
        string $description,
        ?string $reference,
        float $amount,
        ?string $paymentMethod = null,
        ?string $person = null
    ): array {
        $topics = $this->costReport->topics();

        return [
            'date' => $date,
            'date_label' => $date !== '' ? Carbon::parse($date)->format('d M Y') : '',
            'month_key' => $date !== '' ? substr($date, 0, 7) : '',
            'source' => $source,
            'source_key' => $sourceKey,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'accounting_event_key' => self::accountingEventKey($sourceKey, $sourceId),
            'category' => $category,
            'topic' => $topic,
            'topic_label' => $topics[$topic]['label'] ?? ucfirst(str_replace('_', ' ', $topic)),
            'description' => $description,
            'reference' => $reference,
            'amount' => round($amount, 2),
            'payment_method' => $paymentMethod,
            'person' => $person,
            'accounting_txn' => null,
            'accounting_linked' => false,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $ledger
     * @return array<int, array{month: string, label: string, count: int, total: float}>
     */
    public function monthlyTotals(Collection $ledger): array
    {
        return $ledger
            ->filter(fn (array $row) => !empty($row['month_key']))
            ->groupBy('month_key')
            ->map(function (Collection $group, string $month) {
                return [
                    'month' => $month,
                    'label' => Carbon::parse($month . '-01')->format('F Y'),
                    'count' => $group->count(),
                    'total' => round((float) $group->sum('amount'), 2),
                ];
            })
            ->sortKeysDesc()
            ->values()
            ->all();
    }

    /** @param Collection<int, array<string, mixed>> $ledger
     * @return array<int, array{month: string, label: string, total: float, count: int, lines: array<int, array<string, mixed>>}>
     */
    public function ledgerByMonth(Collection $ledger): array
    {
        return $ledger
            ->filter(fn (array $row) => !empty($row['month_key']))
            ->groupBy('month_key')
            ->sortKeysDesc()
            ->map(function (Collection $lines, string $month) {
                return [
                    'month' => $month,
                    'label' => Carbon::parse($month . '-01')->format('F Y'),
                    'total' => round((float) $lines->sum('amount'), 2),
                    'count' => $lines->count(),
                    'lines' => $lines->sortByDesc('date')->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}

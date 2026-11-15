<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ManufacturingJob;
use App\Models\ManufacturingJobAdditionalCost;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\SalesPersonExpense;
use App\Models\VehicleDailyRunItem;
use App\Services\ManufacturingOverheadService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Monthly / period Cost Sheet for Net Profit.
 * Topic buckets match the garment factory cost sheet (Direct → Admin Overheads).
 * Revenue uses collections (payments − refunds), same basis as Business Summary.
 */
class CostReportService
{
    /** @return array<string, array{label: string, label_si: string, description: string, subtopics: array<int, array{name: string, hint: string}>}> */
    public function topics(): array
    {
        return [
            'direct' => [
                'label' => '1. Direct Costs',
                'label_si' => 'සෘජු පිරිවැය',
                'description' => 'ඇඳුමක් නිෂ්පාදනයට කෙලින්ම දායක වන ද්‍රව්‍ය සහ ශ්‍රමය',
                'subtopics' => [
                    ['name' => 'Fabric (රෙදි)', 'hint' => 'Main fabric, lining — material issue (auto) or Expense → Fabric'],
                    ['name' => 'Trims & Accessories', 'hint' => 'Thread, buttons, zippers, elastic, labels, tags — material issue or Trims category'],
                    ['name' => 'Direct Labor (සෘජු ශ්‍රම)', 'hint' => 'Piece rate / sewing wages — job costs or Direct Labor expense'],
                    ['name' => 'Embellishments', 'hint' => 'Embroidery, printing, washing/dyeing — job costs or Embellishments expense'],
                ],
            ],
            'utilities' => [
                'label' => '2. Utility Costs',
                'label_si' => 'පොදු පහසුකම් වියදම්',
                'description' => 'කර්මාන්තශාලාව දුවන්න අත්‍යවශ්‍ය දේ සඳහා මාසික බිල්පත්',
                'subtopics' => [
                    ['name' => 'Electricity (විදුලි)', 'hint' => 'Expense → Electricity'],
                    ['name' => 'Water (වතුර)', 'hint' => 'Expense → Water'],
                    ['name' => 'Communication', 'hint' => 'WiFi / internet / phone — Expense → Communication'],
                ],
            ],
            'maintenance' => [
                'label' => '3. Factory & Machine Maintenance',
                'label_si' => 'නඩත්තු වියදම්',
                'description' => 'නිෂ්පාදනය බාධාවකින් තොරව කරගෙන යාමට',
                'subtopics' => [
                    ['name' => 'Machine Consumables', 'hint' => 'Oil, needles, spare parts'],
                    ['name' => 'Repairs', 'hint' => 'Machine repair, cutter sharpening'],
                    ['name' => 'Factory Maintenance', 'hint' => 'Building repairs, bulbs, etc.'],
                ],
            ],
            'packing_logistics' => [
                'label' => '4. Packing & Logistics',
                'label_si' => 'ඇසුරුම් සහ ප්‍රවාහනය',
                'description' => 'නිමි ඇඳුම් පාරිභෝගිකයා අතට පත් කරන තෙක්',
                'subtopics' => [
                    ['name' => 'Packing Materials', 'hint' => 'Cartons, polybags, hangers, tape'],
                    ['name' => 'Inward Transport', 'hint' => 'Fabric/materials to factory — diesel/petrol'],
                    ['name' => 'Outward Transport', 'hint' => 'Delivery to buyers/shops — vehicle runs, courier'],
                ],
            ],
            'indirect_labor' => [
                'label' => '5. Indirect Labor',
                'label_si' => 'වක්‍ර ශ්‍රම පිරිවැය',
                'description' => 'කෙලින්ම මහන්නේ නැති, නමුත් නිෂ්පාදනයට උදව් වන සේවකයින්',
                'subtopics' => [
                    ['name' => 'Supervisors & Line QCs', 'hint' => 'Payroll (finalized) — avoid double entry as Expense'],
                    ['name' => 'Helpers & Packers', 'hint' => 'Payroll or Indirect Labor expense'],
                    ['name' => 'Mechanic & Security', 'hint' => 'Payroll or Indirect Labor expense'],
                    ['name' => 'Merchandisers & Designers', 'hint' => 'Payroll or Indirect Labor expense'],
                ],
            ],
            'admin_overheads' => [
                'label' => '6. Administration & Fixed Overheads',
                'label_si' => 'පරිපාලන හා ස්ථාවර වියදම්',
                'description' => 'ඇඳුම් මැහුවත් නැතත් මාසිකව අනිවාර්යයෙන්ම යන ස්ථාවර වියදම්',
                'subtopics' => [
                    ['name' => 'Rent (කුලිය)', 'hint' => 'Expense → Rent'],
                    ['name' => 'Stationery & Printing', 'hint' => 'Pens, bill books, printer ink, cost sheets'],
                    ['name' => 'Staff Welfare', 'hint' => 'Tea, meals, medical welfare'],
                    ['name' => 'Depreciation (ක්ෂයවීම)', 'hint' => 'Machine / vehicle depreciation — important for accounts'],
                ],
            ],
        ];
    }

    /** @return array<string, string> Topic key → display label */
    public function topicLabels(): array
    {
        $out = [];
        foreach ($this->topics() as $key => $meta) {
            $out[$key] = $meta['label'];
        }
        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\ExpenseCategory>  $categories
     * @return array<string, array{label: string, label_si: string, categories: \Illuminate\Support\Collection}>
     */
    public function groupCategoriesByTopic(Collection $categories): array
    {
        $grouped = [];
        foreach ($this->topics() as $key => $meta) {
            $grouped[$key] = [
                'label' => $meta['label'],
                'label_si' => $meta['label_si'],
                'categories' => collect(),
            ];
        }

        foreach ($categories as $category) {
            $topic = $this->resolveExpenseTopic((string) ($category->code ?? ''), (string) $category->name);
            if (!isset($grouped[$topic])) {
                $topic = 'admin_overheads';
            }
            $grouped[$topic]['categories']->push($category);
        }

        return $grouped;
    }

    /** @return array<string, mixed> */
    public function build(string $from, string $to, Request $request): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $buckets = [];
        foreach ($this->topics() as $key => $meta) {
            $buckets[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'label_si' => $meta['label_si'],
                'description' => $meta['description'],
                'subtopics' => $meta['subtopics'],
                'lines' => [],
                'total' => 0.0,
            ];
        }

        $orderCount = Order::query()
            ->countedInSales()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orWhere(function ($fallback) use ($from, $to) {
                        $fallback->whereNull('order_date')
                            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    });
            })
            ->count();

        $collections = (float) Payment::query()
            ->customerPayments()
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');
        $refunds = (float) Payment::query()
            ->customerRefunds()
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');
        $revenue = round($collections - $refunds, 2);

        // Manufacturing materials consumed + job additional costs (avoid full PO double-count).
        $jobs = ManufacturingJob::query()
            ->with('additionalCosts')
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('issue_date', '>=', $from)
                    ->whereDate('issue_date', '<=', $to)
                    ->orWhereBetween('updated_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
            })
            ->get();

        $fabric = 0.0;
        $accessory = 0.0;
        $sewing = 0.0;
        $labour = 0.0;
        $embroidery = 0.0;
        $printing = 0.0;
        $washing = 0.0;
        $transport = 0.0;
        $overhead = 0.0;
        $otherJob = 0.0;

        foreach ($jobs as $job) {
            $breakdown = $job->costBreakdown();
            $fabric += (float) $breakdown['fabric'];
            $accessory += (float) $breakdown['accessory'];
            foreach ($job->additionalCosts as $cost) {
                $amount = (float) $cost->amount;
                match ($cost->category) {
                    ManufacturingJobAdditionalCost::CAT_SEWING => $sewing += $amount,
                    ManufacturingJobAdditionalCost::CAT_LABOUR => $labour += $amount,
                    ManufacturingJobAdditionalCost::CAT_EMBROIDERY => $embroidery += $amount,
                    ManufacturingJobAdditionalCost::CAT_PRINTING => $printing += $amount,
                    ManufacturingJobAdditionalCost::CAT_WASHING => $washing += $amount,
                    ManufacturingJobAdditionalCost::CAT_TRANSPORT => $transport += $amount,
                    ManufacturingJobAdditionalCost::CAT_OVERHEAD => $overhead += $amount,
                    default => $otherJob += $amount,
                };
            }
        }

        $this->addLine($buckets, 'direct', 'Fabric (issued material)', $fabric, 'Manufacturing jobs');
        $this->addLine($buckets, 'direct', 'Trims & Accessories (issued)', $accessory, 'Manufacturing jobs');
        $this->addLine($buckets, 'direct', 'Direct Labor — Sewing / Piece rate', $sewing, 'Job additional costs');
        $this->addLine($buckets, 'direct', 'Direct Labor — Labour (job)', $labour, 'Job additional costs');
        $this->addLine($buckets, 'direct', 'Embellishments — Embroidery', $embroidery, 'Job additional costs');
        $this->addLine($buckets, 'direct', 'Embellishments — Printing', $printing, 'Job additional costs');
        $this->addLine($buckets, 'direct', 'Embellishments — Washing / Dyeing', $washing, 'Job additional costs');
        $this->addLine($buckets, 'packing_logistics', 'Production transport (job)', $transport, 'Job additional costs');
        $this->addLine($buckets, 'admin_overheads', 'Production overhead (job)', $overhead, 'Job additional costs');
        $this->addLine($buckets, 'admin_overheads', 'Other production costs (job)', $otherJob, 'Job additional costs');

        // ERP expenses by category code / name keywords.
        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('expense_date', [$from, $to])
            ->get();

        foreach ($expenses->groupBy(fn (Expense $e) => $e->category?->id ?? 0) as $group) {
            /** @var Collection<int, Expense> $group */
            $first = $group->first();
            $code = strtoupper((string) ($first->category?->code ?? ''));
            $name = (string) ($first->category?->name ?? 'Uncategorized');
            $amount = round((float) $group->sum('amount'), 2);
            $topic = $this->mapExpenseCodeToTopic($code, $name);
            $this->addLine($buckets, $topic, $name, $amount, 'Expenses');
        }

        // Sales person field expenses.
        $spExpenses = SalesPersonExpense::query()
            ->whereBetween('expense_date', [$from, $to])
            ->get();
        foreach ($spExpenses->groupBy('type') as $type => $group) {
            $amount = round((float) $group->sum('amount'), 2);
            $label = SalesPersonExpense::typeOptions()[$type] ?? ucfirst((string) $type);
            $topic = in_array($type, [SalesPersonExpense::TYPE_FUEL, SalesPersonExpense::TYPE_TRAVEL], true)
                ? 'packing_logistics'
                : (in_array($type, [SalesPersonExpense::TYPE_FOOD, SalesPersonExpense::TYPE_ACCOMMODATION], true)
                    ? 'admin_overheads'
                    : 'admin_overheads');
            if ($type === SalesPersonExpense::TYPE_SUPPLIES) {
                $topic = 'packing_logistics';
            }
            $this->addLine($buckets, $topic, 'Sales field — ' . $label, $amount, 'Sales person expenses');
        }

        // Vehicle / driver run costs.
        $vehicleCosts = (float) VehicleDailyRunItem::query()
            ->whereHas('run', function ($q) use ($from, $to) {
                $q->whereDate('run_date', '>=', $from)->whereDate('run_date', '<=', $to);
            })
            ->sum('cost');
        $this->addLine($buckets, 'packing_logistics', 'Vehicle / driver run costs', $vehicleCosts, 'Vehicle daily runs');

        // Finalized payroll → Indirect Labor (supervisors, helpers, etc. unless entered as Direct sewing).
        $payroll = (float) PayrollRunItem::query()
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
            ->sum('net_salary');
        $this->addLine($buckets, 'indirect_labor', 'Payroll (finalized net salaries)', $payroll, 'Payroll runs');

        foreach ($buckets as &$bucket) {
            $bucket['total'] = round(collect($bucket['lines'])->sum('amount'), 2);
            $bucket['lines'] = collect($bucket['lines'])
                ->sortByDesc('amount')
                ->values()
                ->all();
        }
        unset($bucket);

        $totalCosts = round(collect($buckets)->sum('total'), 2);
        $netProfit = round($revenue - $totalCosts, 2);

        $overheadService = app(ManufacturingOverheadService::class);
        $simpleAverage = $overheadService->simpleAverageForPeriod($from, $to);
        $expenseBreakdown = [];
        foreach ($expenses->groupBy(fn (Expense $e) => $e->category?->id ?? 0) as $group) {
            $first = $group->first();
            $expenseBreakdown[] = [
                'label' => (string) ($first->category?->name ?? 'Uncategorized'),
                'code' => (string) ($first->category?->code ?? ''),
                'amount' => round((float) $group->sum('amount'), 2),
            ];
        }
        usort($expenseBreakdown, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return [
            'filters' => ['from' => $from, 'to' => $to],
            'revenue' => [
                'collections' => round($collections, 2),
                'refunds' => round($refunds, 2),
                'net' => $revenue,
                'orders' => $orderCount,
                'basis' => 'collections',
            ],
            'topics' => array_values($buckets),
            'summary' => [
                'total_costs' => $totalCosts,
                'net_profit' => $netProfit,
                'job_count' => $jobs->count(),
                'expense_count' => $expenses->count(),
            ],
            'simple_average' => array_merge($simpleAverage, [
                'expense_lines' => $expenseBreakdown,
                'formula' => 'Total Expenses ÷ Finished pieces',
            ]),
            'notes' => [
                'Enter monthly bills (Electricity, Water, Rent, etc.) under ERP → Expenses as usual.',
                'Simple Average: Total Expenses in the period ÷ finished garment pieces (QC finished goods; packing good qty if QC is empty).',
                'Example: Expenses Rs 150,000 ÷ 5,000 pcs = Rs 30.00 overhead per piece — added to each job cost sheet.',
                'Job unit cost = Fabric + Accessories + Sewing/Labour + Overhead (Rs/piece from this month).',
                'Net Profit per piece (example) = Selling price − full unit cost (when product price is set).',
                'Accounting: Expenses post DR Expense (6000) CR Cash; when finished goods are received, overhead is absorbed DR WIP (1320) CR Expense (6000), then DR Finished Goods (1330) CR WIP (1320) at full unit cost.',
                'Direct materials use issued fabric/accessories from manufacturing (not full purchase invoices), so inventory purchases are not double-counted.',
                'Net Profit (report) = Collections − Refunds − all topic costs.',
                'Avoid entering the same salary both as Payroll and as an Expense with code SALARIES.',
            ],
        ];
    }

    private function addLine(array &$buckets, string $topic, string $label, float $amount, string $source): void
    {
        $amount = round($amount, 2);
        if ($amount == 0.0) {
            return;
        }

        $buckets[$topic]['lines'][] = [
            'label' => $label,
            'amount' => $amount,
            'source' => $source,
        ];
    }

    private function mapExpenseCodeToTopic(string $code, string $name): string
    {
        return $this->resolveExpenseTopic($code, $name);
    }

    /** Public helper for Cost Analyst and other reports. */
    public function resolveExpenseTopic(string $code, string $name): string
    {
        $code = strtoupper(trim($code));
        $hay = strtoupper($code . ' ' . $name);

        $map = [
            'FABRIC' => 'direct',
            'TRIMS' => 'direct',
            'ACCESSORIES' => 'direct',
            'DIRECT_LABOR' => 'direct',
            'SEWING' => 'direct',
            'EMBELLISH' => 'direct',
            'EMBROIDERY' => 'direct',
            'PRINTING' => 'direct',
            'WASHING' => 'direct',
            'UTILITIES' => 'utilities',
            'ELECTRICITY' => 'utilities',
            'WATER' => 'utilities',
            'COMMUNICATION' => 'utilities',
            'INTERNET' => 'utilities',
            'MAINT' => 'maintenance',
            'MAINTENANCE' => 'maintenance',
            'MACHINE_CONSUMABLES' => 'maintenance',
            'MACHINE_REPAIR' => 'maintenance',
            'FACTORY_MAINT' => 'maintenance',
            'PACKING' => 'packing_logistics',
            'TRANSPORT' => 'packing_logistics',
            'INWARD_TRANSPORT' => 'packing_logistics',
            'OUTWARD_TRANSPORT' => 'packing_logistics',
            'LOGISTICS' => 'packing_logistics',
            'SALARIES' => 'indirect_labor',
            'INDIRECT_LABOR' => 'indirect_labor',
            'WAGES' => 'indirect_labor',
            'OFFICE' => 'admin_overheads',
            'RENT' => 'admin_overheads',
            'STATIONERY' => 'admin_overheads',
            'WELFARE' => 'admin_overheads',
            'DEPRECIATION' => 'admin_overheads',
            'MISC' => 'admin_overheads',
        ];

        if (isset($map[$code])) {
            return $map[$code];
        }

        return match (true) {
            str_contains($hay, 'FABRIC') || str_contains($hay, 'TRIM') || str_contains($hay, 'SEW') || str_contains($hay, 'EMBROID') || str_contains($hay, 'WASH') || str_contains($hay, 'PRINT') || str_contains($hay, 'DYE') => 'direct',
            str_contains($hay, 'ELECTRIC') || str_contains($hay, 'WATER') || str_contains($hay, 'UTILIT') || str_contains($hay, 'INTERNET') || str_contains($hay, 'PHONE') => 'utilities',
            str_contains($hay, 'MAINT') || str_contains($hay, 'REPAIR') || str_contains($hay, 'NEEDLE') || str_contains($hay, 'MACHINE') => 'maintenance',
            str_contains($hay, 'PACK') || str_contains($hay, 'TRANSPORT') || str_contains($hay, 'COURIER') || str_contains($hay, 'DIESEL') || str_contains($hay, 'PETROL') || str_contains($hay, 'FUEL') => 'packing_logistics',
            str_contains($hay, 'SALAR') || str_contains($hay, 'WAGE') || str_contains($hay, 'SUPERVIS') || str_contains($hay, 'HELPER') || str_contains($hay, 'SECURITY') => 'indirect_labor',
            default => 'admin_overheads',
        };
    }
}

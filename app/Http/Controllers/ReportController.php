<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariation;
use App\Models\Purchase;
use App\Models\ProductionLog;
use App\Models\QcCheck;
use App\Models\Sale;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\User;
use App\Services\ExpenseLedgerService;
use App\Services\BusinessSummaryReportService;
use App\Services\CostAnalystReportService;
use App\Services\CostReportService;
use App\Services\ManufacturingReportService;
use App\Services\ProductFinalCostReportService;
use App\Services\ProductionCostReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('erp.reports.index');
    }

    public function show(Request $request, string $report)
    {
        [$from, $to, $selectedMonth] = $this->resolveReportDates($request);

        if ($report === 'manufacturing') {
            $data = app(ManufacturingReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.manufacturing', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'cost-analysis') {
            $this->requireManufacturingCosts();
            $data = app(ProductionCostReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.cost-analysis', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'business-summary') {
            $data = app(BusinessSummaryReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.business-summary', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'product-final-cost') {
            $this->requireManufacturingCosts();
            $data = app(ProductFinalCostReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.product-final-cost', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'cost-analyst') {
            $this->requireManufacturingCosts();
            $data = app(CostAnalystReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.cost-analyst', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'cost-report') {
            $this->requireManufacturingCosts();
            $data = app(CostReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.cost-report', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'trending-products') {
            $data = app(\App\Services\TrendingProductsReportService::class)->buildIndex($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.trending-products.index', compact('report', 'title', 'data', 'from', 'to'));
        }

        if ($report === 'product-stock') {
            return $this->showProductStockReport($request);
        }

        if ($report === 'orders-analysis') {
            return $this->showOrdersAnalysisReport($request);
        }

        if ($report === 'orders-manufacturing') {
            return $this->showOrdersManufacturingReport($request);
        }

        if ($report === 'inventory') {
            return $this->showInventoryReport($request);
        }

        if ($report === 'pending-cheques') {
            $data = app(\App\Services\PendingChequesReportService::class)->build($request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.pending-cheques', compact('report', 'title', 'data'));
        }

        if ($report === 'sales-person-expenses') {
            $data = app(\App\Services\SalesPersonExpenseReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            return view('erp.reports.sales-person-expenses', compact('report', 'title', 'data', 'from', 'to'));
        }

        $data = $this->getReportData($report, $from, $to, $request);
        $title = $this->getReportTitle($report);

        return view('erp.reports.show', compact('report', 'title', 'data', 'from', 'to', 'selectedMonth'));
    }

    public function export(Request $request, string $report): StreamedResponse|\Illuminate\Http\Response
    {
        [$from, $to] = array_slice($this->resolveReportDates($request), 0, 2);
        $format = $request->get('format', 'pdf');

        if ($report === 'manufacturing') {
            $data = app(ManufacturingReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            if ($format === 'csv' || $format === 'excel') {
                return $this->exportCsv($report, $title, $data, $from, $to);
            }

            $pdf = Pdf::loadView('erp.reports.manufacturing_pdf', compact('report', 'title', 'data', 'from', 'to'))
                ->setPaper('a4', 'landscape');

            return $pdf->download('manufacturing-report-' . $from . '-to-' . $to . '.pdf');
        }

        if ($report === 'trending-products') {
            $data = app(\App\Services\TrendingProductsReportService::class)->buildIndex($from, $to, $request);
            $title = $this->getReportTitle($report);

            if ($format === 'csv' || $format === 'excel') {
                return $this->exportCsv($report, $title, $data, $from, $to);
            }

            return $this->exportPdf($report, $title, $data, $from, $to);
        }

        if ($report === 'orders-manufacturing') {
            return $this->exportOrdersManufacturingCsv($request);
        }

        if ($report === 'sales-person-expenses') {
            $data = app(\App\Services\SalesPersonExpenseReportService::class)->build($from, $to, $request);
            $title = $this->getReportTitle($report);

            if ($format === 'csv' || $format === 'excel') {
                return $this->exportCsv($report, $title, $data, $from, $to);
            }

            $pdf = Pdf::loadView('erp.reports.pdf', compact('report', 'title', 'data', 'from', 'to'))
                ->setPaper('a4', 'landscape');

            return $pdf->download(str_replace(' ', '-', $title) . '-' . $from . '-to-' . $to . '.pdf');
        }

        if (in_array($report, ['cost-analysis', 'business-summary', 'cost-report', 'cost-analyst', 'product-final-cost'], true)) {
            if (in_array($report, ['cost-analysis', 'cost-report', 'cost-analyst', 'product-final-cost'], true)) {
                $this->requireManufacturingCosts();
            }
            $data = match ($report) {
                'cost-analysis' => app(ProductionCostReportService::class)->build($from, $to, $request),
                'cost-report' => app(CostReportService::class)->build($from, $to, $request),
                'cost-analyst' => app(CostAnalystReportService::class)->build($from, $to, $request),
                'product-final-cost' => app(ProductFinalCostReportService::class)->build($from, $to, $request),
                default => app(BusinessSummaryReportService::class)->build($from, $to, $request),
            };
            $title = $this->getReportTitle($report);

            if ($format === 'csv' || $format === 'excel') {
                return $this->exportCsv($report, $title, $data, $from, $to);
            }

            $pdf = Pdf::loadView('erp.reports.analysis_pdf', compact('report', 'title', 'data', 'from', 'to'))
                ->setPaper('a4', 'portrait');

            return $pdf->download($report . '-' . $from . '-to-' . $to . '.pdf');
        }

        if ($report === 'inventory' && in_array($format, ['csv', 'excel'], true)) {
            return $this->showInventoryReport($request->merge(['format' => $format]));
        }

        $data = $this->getReportData($report, $from, $to, $request);
        $title = $this->getReportTitle($report);

        if ($format === 'csv' || $format === 'excel') {
            return $this->exportCsv($report, $title, $data, $from, $to);
        }

        return $this->exportPdf($report, $title, $data, $from, $to);
    }

    protected function requireManufacturingCosts(): void
    {
        if (! auth()->user()?->can_access('manufacturing.costs')) {
            abort(403, "You don't have permission to access this area. Required: manufacturing.costs");
        }
    }

    protected function getReportTitle(string $report): string
    {
        $titles = [
            'profit-loss' => 'Profit / Loss Report',
            'purchase-sale' => 'Purchase & Sale',
            'tax' => 'Tax Report',
            'supplier-customer' => 'Supplier & Customer Report',
            'supplier' => 'Supplier Report',
            'customer' => 'Customer Report',
            'customer-groups' => 'Customer Groups Report',
            'stock' => 'Stock Report',
            'stock-adjustment' => 'Stock Adjustment Report',
            'trending-products' => 'Trending Products',
            'items' => 'Items Report',
            'product-purchase' => 'Product Purchase Report',
            'product-sell' => 'Product Sell Report',
            'purchase-payment' => 'Purchase Payment Report',
            'sell-payment' => 'Sell Payment Report',
            'expense' => 'Unified Expense Report',
            'register' => 'Register Report',
            'sales-representative' => 'Sales Person Report',
            'activity-log' => 'Activity Log',
            'purchase' => 'Purchase Report',
            'sale' => 'Sale Report',
            'manufacturing' => 'Manufacturing Report',
            'cost-analysis' => 'Production Cost Analysis',
            'product-final-cost' => 'Product Final Cost Report',
            'cost-analyst' => 'Cost Analyst Report',
            'business-summary' => 'Business Summary Report',
            'cost-report' => 'Cost Report / Cost Sheet',
            'inventory' => 'Inventory Report',
            'pending-cheques' => 'Pending Cheques Report',
            'sales-person-expenses' => 'Sales Person Expenses Report',
            'orders-manufacturing' => 'Orders Manufacturing Report',
        ];
        return $titles[$report] ?? ucfirst(str_replace('-', ' ', $report));
    }

    protected function getReportData(string $report, string $from, string $to, Request $request): array
    {
        $dateFilter = function ($q) use ($from, $to) {
            $q->where(function ($query) use ($from, $to) {
                $query->whereBetween('order_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orWhere(function ($fallbackQuery) use ($from, $to) {
                        $fallbackQuery->whereNull('order_date')
                            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    });
            });
        };

        switch ($report) {
            case 'purchase':
                $purchases = Purchase::with(['supplier', 'payments'])
                    ->reportable()
                    ->whereDate('date', '>=', $from)
                    ->whereDate('date', '<=', $to)
                    ->orderBy('date')
                    ->get();
                $purchaseTotal = $purchases->sum('total');
                $purchasePaid = $purchases->sum(fn ($p) => $p->total_paid);
                return [
                    'purchases' => $purchases,
                    'total' => $purchaseTotal,
                    'total_paid' => $purchasePaid,
                    'count' => $purchases->count(),
                ];

            case 'sale':
                return $this->buildSaleReportData($request, $dateFilter);

            case 'purchase-sale':
                $orders = Order::with(['customer', 'saleReturns'])
                    ->countedInSales()
                    ->where($dateFilter)
                    ->orderByRaw('COALESCE(order_date, created_at) DESC')
                    ->get();
                return ['orders' => $orders, 'total' => $orders->sum(fn ($o) => $o->net_total)];

            case 'tax':
                return ['items' => [], 'total_tax' => 0, 'message' => 'Tax data can be added when tax fields are available.'];

            case 'supplier':
                $suppliers = Supplier::orderBy('name')->get();
                return [
                    'suppliers' => $suppliers,
                    'count' => $suppliers->count(),
                ];

            case 'customer':
                $customers = Customer::withCount('orders')->withSum('orders as total_sales', 'total_amount')->orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get();
                return [
                    'customers' => $customers,
                    'count' => $customers->count(),
                    'total_sales' => $customers->sum('total_sales'),
                ];

            case 'supplier-customer':
                $customers = Customer::withCount('orders')->orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get();
                return ['customers' => $customers];

            case 'customer-groups':
                $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get();
                return ['customers' => $customers, 'message' => 'Group by category when customer groups exist.'];

            case 'stock':
                $products = Product::with('unit')->where('product_type', 'simple')->orderBy('name')->get();
                return ['products' => $products];

            case 'stock-adjustment':
                $ledgers = StockLedger::with('product', 'creator')
                    ->where('transaction_type', 'adjustment')
                    ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orderByDesc('created_at')
                    ->get();
                return ['ledgers' => $ledgers];

            case 'trending-products':
                $items = OrderItem::query()
                    ->select(
                        'product_id',
                        DB::raw('SUM(GREATEST(quantity - COALESCE(quantity_returned, 0), 0)) as total_qty'),
                        DB::raw('SUM(GREATEST(quantity - COALESCE(quantity_returned, 0), 0) * unit_price) as total_sales')
                    )
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereNotIn('orders.status', Order::excludedFromSalesStats())
                    ->whereBetween('orders.order_date', [$from, $to])
                    ->groupBy('product_id')
                    ->orderByDesc('total_qty')
                    ->limit(50)
                    ->get();
                $items->load('product');
                return ['items' => $items];

            case 'items':
                $products = Product::with(['unit', 'category'])->orderBy('name')->get();
                return ['products' => $products];

            case 'product-purchase':
                return ['items' => [], 'message' => 'Purchase data can be added when purchase module exists.'];

            case 'product-sell':
                $items = OrderItem::query()
                    ->select(
                        'product_id',
                        DB::raw('SUM(GREATEST(quantity - COALESCE(quantity_returned, 0), 0)) as total_qty'),
                        DB::raw('SUM(GREATEST(quantity - COALESCE(quantity_returned, 0), 0) * unit_price) as total_amount')
                    )
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereBetween('orders.order_date', [$from, $to])
                    ->groupBy('product_id')
                    ->get();
                $items->load('product');
                return ['items' => $items];

            case 'purchase-payment':
                $payments = \App\Models\PurchasePayment::with(['supplier', 'purchase'])
                    ->whereHas('purchase', fn ($q) => $q->reportable())
                    ->whereDate('payment_date', '>=', $from)
                    ->whereDate('payment_date', '<=', $to)
                    ->orderBy('payment_date')
                    ->get();
                return [
                    'payments' => $payments,
                    'total' => $payments->sum('amount'),
                    'count' => $payments->count(),
                ];

            case 'sell-payment':
                $payments = Payment::with('order')
                    ->whereHas('order', fn ($q) => $q->where($dateFilter))
                    ->orderBy('payment_date')
                    ->get();
                return ['payments' => $payments, 'total' => $payments->sum('amount')];

            case 'expense':
                $source = (string) $request->get('source', 'all');
                $ledgerService = app(ExpenseLedgerService::class);
                $ledger = $ledgerService->collect($from, $to, $source);
                $sourceTotals = $ledger->groupBy('source_key')->map(fn ($group) => [
                    'label' => (string) ($group->first()['source'] ?? ''),
                    'count' => $group->count(),
                    'total' => round((float) $group->sum('amount'), 2),
                ])->sortByDesc('total')->values()->all();

                return [
                    'ledger' => $ledger->sortByDesc('date')->values()->all(),
                    'ledger_by_month' => $ledgerService->ledgerByMonth($ledger),
                    'monthly_totals' => $ledgerService->monthlyTotals($ledger),
                    'total' => round((float) $ledger->sum('amount'), 2),
                    'count' => $ledger->count(),
                    'accounting_linked_count' => $ledger->where('accounting_linked', true)->count(),
                    'source_totals' => $sourceTotals,
                    'source_options' => ExpenseLedgerService::SOURCE_OPTIONS,
                    'filters' => [
                        'source' => $source,
                        'month' => (string) ($request->get('month') ?: $this->monthKeyForRange($from, $to)),
                    ],
                ];

            case 'profit-loss':
                $summary = app(BusinessSummaryReportService::class)->build($from, $to, $request);

                return [
                    'total_revenue' => $summary['sales']['total'],
                    'total_cost' => $summary['purchases']['total'] + $summary['expenses']['total'] + $summary['production']['additional'],
                    'profit' => $summary['summary']['estimated_margin'],
                    'orders_count' => $summary['sales']['orders'],
                    'summary' => $summary,
                ];

            case 'register':
                $orders = Order::with(['customer', 'saleReturns'])
                    ->where($dateFilter)
                    ->orderByRaw('COALESCE(order_date, created_at) DESC')
                    ->get();
                $payments = Payment::customerPayments()->whereHas('order', fn ($q) => $q->where($dateFilter))->get();
                $refunds = Payment::customerRefunds()->whereHas('order', fn ($q) => $q->where($dateFilter))->get();
                return [
                    'orders' => $orders,
                    'payments' => $payments,
                    'total_sales' => $orders->sum(fn ($o) => $o->net_total),
                    'total_collected' => (float) $payments->sum('amount') - (float) $refunds->sum('amount'),
                ];

            case 'sales-representative':
                $ordersByUser = Order::query()
                    ->with(['salesPerson', 'saleReturns'])
                    ->where($dateFilter)
                    ->whereNotNull('user_id')
                    ->get()
                    ->groupBy('user_id');

                $reps = $ordersByUser->map(function ($group, $userId) {
                    return (object) [
                        'user_id' => $userId,
                        'salesPerson' => $group->first()->salesPerson,
                        'order_count' => $group->count(),
                        'total_sales' => $group->sum(fn ($o) => $o->net_total),
                    ];
                })->values();

                return ['reps' => $reps];

            case 'activity-log':
                $orders = Order::with('customer', 'salesPerson')
                    ->where($dateFilter)
                    ->orderByRaw('COALESCE(order_date, created_at) DESC')
                    ->limit(100)
                    ->get();
                return ['orders' => $orders];

            default:
                return ['message' => 'Report type not implemented.'];
        }
    }

    protected function buildSaleReportData(Request $request, callable $dateFilter): array
    {
        $saleQuery = Order::query()
            ->countedInSales()
            ->with(['customer', 'salesPerson', 'saleReturns'])
            ->withSum(['payments as payments_sum_amount' => fn ($q) => $q->effectiveForOrderPaid()], 'amount')
            ->withSum(['payments as refunds_sum_amount' => fn ($q) => $q->customerRefunds()], 'amount')
            ->where($dateFilter);

        $filters = $this->applySaleReportFilters($saleQuery, $request);

        $orders = $saleQuery
            ->orderByRaw('COALESCE(order_date, created_at) DESC')
            ->get();

        $totalSales = (float) $orders->sum(fn ($o) => $o->net_total);
        $totalPaid = (float) $orders->sum(fn ($o) => (float) ($o->payments_sum_amount ?? 0) - (float) ($o->refunds_sum_amount ?? 0));
        $totalDue = max(0, round($totalSales - max(0, $totalPaid), 2));

        $salesPersonIds = $this->salesPersonUserIds();
        $bySalesPerson = $orders
            ->groupBy(fn ($o) => $o->user_id ?: 0)
            ->map(function ($group, $userId) use ($salesPersonIds) {
                $first = $group->first();
                $paid = (float) $group->sum(fn ($o) => (float) ($o->payments_sum_amount ?? 0) - (float) ($o->refunds_sum_amount ?? 0));
                $sales = (float) $group->sum(fn ($o) => $o->net_total);

                return (object) [
                    'user_id' => $userId ?: null,
                    'salesPerson' => $first->salesPerson,
                    'label' => $userId && in_array((int) $userId, $salesPersonIds, true)
                        ? ($first->salesPerson?->name ?? 'Sales Person')
                        : 'Retail / Walk-in',
                    'order_count' => $group->count(),
                    'total_sales' => $sales,
                    'total_paid' => $paid,
                    'total_due' => max(0, round($sales - max(0, $paid), 2)),
                ];
            })
            ->sortByDesc('total_sales')
            ->values();

        $byBusiness = $orders
            ->groupBy(fn ($o) => $o->customer_id ?: 0)
            ->map(function ($group) {
                $customer = $group->first()->customer;
                $business = trim((string) ($customer?->business_name ?? ''));
                $paid = (float) $group->sum(fn ($o) => (float) ($o->payments_sum_amount ?? 0) - (float) ($o->refunds_sum_amount ?? 0));
                $sales = (float) $group->sum(fn ($o) => $o->net_total);

                return (object) [
                    'label' => $business !== '' ? $business : ($customer?->display_name ?? 'Walk-in'),
                    'order_count' => $group->count(),
                    'total_sales' => $sales,
                    'total_paid' => $paid,
                    'total_due' => max(0, round($sales - max(0, $paid), 2)),
                ];
            })
            ->sortByDesc('total_sales')
            ->values();

        return [
            'orders' => $orders,
            'bySalesPerson' => $bySalesPerson,
            'byBusiness' => $byBusiness,
            'total' => $totalSales,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'count' => $orders->count(),
            'filters' => $filters,
            'salesPersons' => User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get(['id', 'name', 'business_name']),
            'customer_type' => $filters['sale_type'],
        ];
    }

    protected function salesPersonUserIds(): array
    {
        return User::where('role', 'sales_person')->pluck('id')->all();
    }

    protected function applySaleReportFilters($query, Request $request): array
    {
        $saleType = $request->get('sale_type', $request->get('customer_type', 'all'));
        $salesPersonId = $request->get('sales_person_id');
        $customerId = $request->get('customer_id');
        $status = $request->get('status');
        $paymentState = $request->get('payment_state');

        $salesPersonIds = $this->salesPersonUserIds();

        if ($saleType === 'wholesale') {
            $query->whereIn('user_id', $salesPersonIds);
        } elseif ($saleType === 'retail') {
            $query->where(function ($q) use ($salesPersonIds) {
                $q->whereNull('user_id')->orWhereNotIn('user_id', $salesPersonIds);
            });
        }

        if ($salesPersonId) {
            $query->where('user_id', $salesPersonId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($paymentState === 'paid') {
            $paidSub = Order::effectivePaidAmountSubquery();
            $query->whereRaw(Order::invoiceTotalSql() . " <= {$paidSub}");
        } elseif ($paymentState === 'due') {
            $paidSub = Order::effectivePaidAmountSubquery();
            $query->whereRaw(Order::invoiceTotalSql() . " > {$paidSub}");
        }

        return [
            'sale_type' => $saleType,
            'sales_person_id' => $salesPersonId,
            'customer_id' => $customerId,
            'status' => $status,
            'payment_state' => $paymentState,
        ];
    }

    protected function exportCsv(string $report, string $title, array $data, string $from, string $to): StreamedResponse
    {
        $filename = str_replace(' ', '-', $title) . '-' . $from . '-to-' . $to . '.csv';

        return response()->streamDownload(function () use ($report, $data) {
            $out = fopen('php://output', 'w');
            $this->writeCsvRows($out, $report, $data);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function writeCsvRows($out, string $report, array $data): void
    {
        switch ($report) {
            case 'profit-loss':
                fputcsv($out, ['Total Revenue', 'Total Cost', 'Profit', 'Orders Count']);
                fputcsv($out, [
                    $data['total_revenue'] ?? 0,
                    $data['total_cost'] ?? 0,
                    $data['profit'] ?? 0,
                    $data['orders_count'] ?? 0,
                ]);
                break;
            case 'stock':
                fputcsv($out, ['Product', 'SKU', 'Current Stock', 'Unit']);
                foreach ($data['products'] ?? [] as $p) {
                    fputcsv($out, [$p->name, $p->sku ?? '', $p->current_stock ?? 0, $p->unit?->short_name ?? '']);
                }
                break;
            case 'purchase-payment':
                fputcsv($out, ['Date', 'Supplier', 'PO / Ref', 'Amount', 'Method', 'Reference']);
                foreach ($data['payments'] ?? [] as $p) {
                    fputcsv($out, [
                        $p->payment_date?->format('Y-m-d'),
                        $p->supplier?->name ?? '',
                        $p->purchase?->purchase_no ?? '—',
                        $p->amount,
                        $p->payment_method ?? '',
                        $p->reference ?? '',
                    ]);
                }
                break;
            case 'sell-payment':
                fputcsv($out, ['Date', 'Order', 'Amount', 'Method', 'Reference', 'Cash discount on next payment']);
                foreach ($data['payments'] ?? [] as $p) {
                    fputcsv($out, [
                        $p->payment_date?->format('Y-m-d'),
                        $p->order?->order_number ?? '',
                        $p->amount,
                        $p->payment_method ?? '',
                        $p->reference_number ?? '',
                        $p->cashNextPaymentDiscountLine() ?? '',
                    ]);
                }
                break;
            case 'sales-representative':
                fputcsv($out, ['Sales Person', 'Order Count', 'Total Sales']);
                foreach ($data['reps'] ?? [] as $r) {
                    fputcsv($out, [$r->salesPerson?->name ?? 'N/A', $r->order_count, $r->total_sales]);
                }
                break;
            case 'sales-person-expenses':
                fputcsv($out, ['BY SALES PERSON']);
                fputcsv($out, ['Sales Person', 'Lines', 'With Receipt', 'Total (Rs)']);
                foreach ($data['byPerson'] ?? [] as $row) {
                    fputcsv($out, [$row->name, $row->count, $row->with_slip, $row->total]);
                }
                fputcsv($out, []);
                fputcsv($out, ['BY TYPE']);
                fputcsv($out, ['Type', 'Lines', 'Total (Rs)']);
                foreach ($data['byType'] ?? [] as $row) {
                    fputcsv($out, [$row->label, $row->count, $row->total]);
                }
                fputcsv($out, []);
                fputcsv($out, ['EXPENSE LINES']);
                fputcsv($out, ['Date', 'Sales Person', 'Type', 'Description', 'Route', 'Amount (Rs)', 'Receipt', 'Notes']);
                foreach ($data['expenses'] ?? [] as $expense) {
                    fputcsv($out, [
                        $expense->expense_date?->format('Y-m-d'),
                        $expense->user?->name ?? '',
                        $expense->typeLabel(),
                        $expense->description,
                        $expense->routeRun?->salesRoute?->pathLabel() ?? '',
                        $expense->amount,
                        $expense->slip_path ? 'Yes' : 'No',
                        $expense->notes ?? '',
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Total (Rs)', $data['total'] ?? 0]);
                fputcsv($out, ['Lines', $data['count'] ?? 0]);
                fputcsv($out, ['With receipt', $data['withSlipCount'] ?? 0]);
                break;
            case 'product-sell':
                fputcsv($out, ['Product', 'Quantity Sold', 'Total Amount']);
                foreach ($data['items'] ?? [] as $i) {
                    fputcsv($out, [$i->product?->name ?? 'N/A', $i->total_qty, $i->total_amount]);
                }
                break;
            case 'supplier':
                fputcsv($out, ['Code', 'Supplier', 'Company', 'Phone', 'Total Purchases (Rs)', 'Total Paid (Rs)', 'Outstanding (Rs)']);
                foreach ($data['suppliers'] ?? [] as $s) {
                    fputcsv($out, [
                        $s->code ?? '',
                        $s->name,
                        $s->company_name ?? '',
                        $s->phone ?? '',
                        $s->total_purchases ?? 0,
                        $s->total_paid ?? 0,
                        $s->outstanding_balance ?? 0,
                    ]);
                }
                break;
            case 'customer':
                fputcsv($out, ['Customer', 'Phone', 'Email', 'Orders', 'Total Sales (Rs)']);
                foreach ($data['customers'] ?? [] as $c) {
                    fputcsv($out, [
                        $c->display_name,
                        $c->phone ?? '',
                        $c->email ?? '',
                        $c->orders_count ?? 0,
                        $c->total_sales ?? 0,
                    ]);
                }
                break;
            case 'supplier-customer':
                fputcsv($out, ['Customer', 'Phone', 'Orders Count']);
                foreach ($data['customers'] ?? [] as $c) {
                    fputcsv($out, [$c->display_name, $c->phone ?? '', $c->orders_count ?? 0]);
                }
                break;
            case 'stock-adjustment':
                fputcsv($out, ['Date', 'Product', 'Quantity', 'Notes', 'By']);
                foreach ($data['ledgers'] ?? [] as $l) {
                    fputcsv($out, [
                        $l->created_at->format('Y-m-d H:i'),
                        $l->product?->name ?? '',
                        $l->quantity,
                        $l->notes ?? '',
                        $l->creator?->name ?? '',
                    ]);
                }
                break;
            case 'trending-products':
                fputcsv($out, ['Rank', 'Product', 'SKU', 'Qty Sold', 'POS Retail Qty', 'Wholesale Qty', 'Growth %', 'Total Sales', 'Top Variation', 'Channel']);
                foreach ($data['rows'] ?? [] as $row) {
                    fputcsv($out, [
                        $row['rank'] ?? '',
                        $row['product']->name ?? '',
                        $row['product']->sku ?? '',
                        $row['total_qty'] ?? 0,
                        $row['retail_qty'] ?? 0,
                        $row['wholesale_qty'] ?? 0,
                        $row['growth_percent'] ?? 0,
                        $row['total_sales'] ?? 0,
                        $row['top_variation_name'] ?? '',
                        $data['filters']['channel_label'] ?? 'All',
                    ]);
                }
                break;
            case 'expense':
                fputcsv($out, ['MONTHLY SUMMARY']);
                fputcsv($out, ['Month', 'Lines', 'Total (Rs)']);
                foreach ($data['monthly_totals'] ?? [] as $mt) {
                    fputcsv($out, [$mt['label'] ?? '', $mt['count'] ?? 0, $mt['total'] ?? 0]);
                }
                fputcsv($out, []);
                fputcsv($out, ['ALL LINES']);
                fputcsv($out, ['Date', 'Source', 'Category', 'Description', 'Reference', 'Payment', 'Person', 'Amount (Rs)', 'Accounting TXN']);
                foreach ($data['ledger_by_month'] ?? [] as $monthGroup) {
                    foreach ($monthGroup['lines'] ?? [] as $line) {
                        fputcsv($out, [
                            $line['date_label'] ?? $line['date'] ?? '',
                            $line['source'] ?? '',
                            $line['category'] ?? '',
                            $line['description'] ?? '',
                            $line['reference'] ?? '',
                            $line['payment_method'] ?? '',
                            $line['person'] ?? '',
                            $line['amount'] ?? 0,
                            $line['accounting_txn'] ?? '',
                        ]);
                    }
                }
                fputcsv($out, []);
                fputcsv($out, ['Total (Rs)', $data['total'] ?? 0]);
                fputcsv($out, ['Lines', $data['count'] ?? 0]);
                fputcsv($out, ['Linked to Accounting', $data['accounting_linked_count'] ?? 0]);
                break;
            case 'purchase':
                fputcsv($out, ['Date', 'PO Number', 'Supplier', 'Status', 'Total (Rs)', 'Paid (Rs)', 'Balance (Rs)']);
                foreach ($data['purchases'] ?? [] as $p) {
                    fputcsv($out, [
                        $p->date?->format('Y-m-d'),
                        $p->purchase_no ?? '',
                        $p->supplier?->name ?? '',
                        $p->status ?? '',
                        $p->total ?? 0,
                        $p->total_paid ?? 0,
                        $p->balance ?? 0,
                    ]);
                }
                break;
            case 'sale':
                fputcsv($out, ['Date', 'Order #', 'Business name', 'Type', 'Sales Person', 'Status', 'Total (Rs)', 'Paid (Rs)', 'Due (Rs)']);
                foreach ($data['orders'] ?? [] as $o) {
                    $paid = (float) ($o->payments_sum_amount ?? 0);
                    $total = $o->invoice_total;
                    $isWholesale = $o->salesPerson && $o->salesPerson->role === 'sales_person';
                    $business = trim((string) ($o->customer?->business_name ?? ''));
                    fputcsv($out, [
                        ($o->order_date ?? $o->created_at)?->format('Y-m-d'),
                        $o->order_number ?? '',
                        $business !== '' ? $business : ($o->customer?->display_name ?? '—'),
                        $isWholesale ? 'Wholesale' : 'Retail',
                        $isWholesale ? ($o->salesPerson->name ?? '—') : '—',
                        $o->status ?? '',
                        $total,
                        $paid,
                        max(0, $total - $paid),
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Business Summary']);
                fputcsv($out, ['Business name', 'Orders', 'Total Sales (Rs)', 'Paid (Rs)', 'Due (Rs)']);
                foreach ($data['byBusiness'] ?? [] as $row) {
                    fputcsv($out, [
                        $row->label,
                        $row->order_count,
                        $row->total_sales,
                        $row->total_paid,
                        $row->total_due,
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Sales Person Summary']);
                fputcsv($out, ['Sales Person', 'Orders', 'Total Sales (Rs)', 'Paid (Rs)', 'Due (Rs)']);
                foreach ($data['bySalesPerson'] ?? [] as $row) {
                    fputcsv($out, [
                        $row->label,
                        $row->order_count,
                        $row->total_sales,
                        $row->total_paid,
                        $row->total_due,
                    ]);
                }
                break;
            case 'manufacturing':
                fputcsv($out, ['=== JOBS ===']);
                fputcsv($out, ['Job #', 'Style', 'Status', 'Issue Date', 'Cut Pcs', 'Line Target', 'Line Produced', 'Washing Sent', 'Fabric Cost', 'Accessory Cost', 'Added Cost', 'Total Cost', 'Cost Per Pc']);
                foreach ($data['job_details'] ?? [] as $row) {
                    $j = $row['job'];
                    fputcsv($out, [
                        $j->job_number,
                        $j->style_ref ?? '',
                        $j->status,
                        $j->issue_date?->format('Y-m-d'),
                        $row['cut_pieces'],
                        $row['line_target'],
                        $row['line_produced'],
                        $row['washing_sent'],
                        $row['fabric_cost'] ?? 0,
                        $row['accessory_cost'] ?? 0,
                        $row['additional_cost'] ?? 0,
                        $row['total_cost'] ?? 0,
                        $row['cost_per_unit'] ?? 0,
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['=== LINE PRODUCTION (period) ===']);
                fputcsv($out, ['Job', 'Line', 'Job Target', 'Total Produced', 'Period Output', 'Period Target']);
                foreach ($data['production']['job_rows'] ?? [] as $prow) {
                    foreach ($prow['lines'] as $line) {
                        fputcsv($out, [
                            $prow['job']->job_number,
                            $line['line_name'],
                            $line['job_target'],
                            $line['total_produced'],
                            $line['period_produced'],
                            $line['period_target'],
                        ]);
                    }
                }
                fputcsv($out, []);
                fputcsv($out, ['=== WASHING ===']);
                fputcsv($out, ['GP ID', 'Type', 'Date', 'Job', 'Sent', 'Received', 'Status', 'Wash Type', 'Factory', 'Vehicle']);
                foreach ($data['washing_rows'] ?? [] as $w) {
                    fputcsv($out, [
                        'GP-' . $w->id,
                        $w->isReceiptBatch() ? 'Receipt' : 'Gate Pass',
                        $w->created_at->format('Y-m-d'),
                        $w->job?->job_number ?? '',
                        $w->isReceiptBatch() ? '' : $w->quantity_sent,
                        $w->isReceiptBatch() ? $w->quantity_sent : ($w->quantity_returned ?? 0),
                        $w->status_label,
                        $w->washing_type_label ?? '',
                        $w->washing_factory_name ?? '',
                        $w->vehicle_number ?? '',
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['=== QC ===']);
                fputcsv($out, ['Date', 'Job', 'Checked', 'Finished', 'Damaged']);
                foreach ($data['qc_checks'] ?? [] as $qc) {
                    fputcsv($out, [
                        $qc->created_at->format('Y-m-d'),
                        $qc->washingTransfer?->job?->job_number ?? '',
                        $qc->total_checked,
                        $qc->finished_good_qty,
                        $qc->damaged_qty,
                    ]);
                }
                break;
            case 'cost-analysis':
                fputcsv($out, ['=== CATEGORY TOTALS (Actual Job Costs) ===']);
                fputcsv($out, ['Category', 'Amount (Rs)']);
                foreach ($data['category_totals'] ?? [] as $cat) {
                    if (($cat['amount'] ?? 0) > 0) {
                        fputcsv($out, [$cat['label'], $cat['amount']]);
                    }
                }
                fputcsv($out, []);
                fputcsv($out, ['Fabric', $data['totals']['fabric'] ?? 0]);
                fputcsv($out, ['Accessory', $data['totals']['accessory'] ?? 0]);
                fputcsv($out, ['Additional Total', $data['totals']['additional'] ?? 0]);
                fputcsv($out, ['Production Total', $data['totals']['production'] ?? 0]);
                fputcsv($out, []);
                fputcsv($out, ['=== JOB COST BREAKDOWN ===']);
                fputcsv($out, ['Job #', 'Product', 'Fabric', 'Accessory', 'Transport', 'Sewing', 'Washing', 'Other Added', 'Total', 'Cost/Pc']);
                foreach ($data['job_rows'] ?? [] as $row) {
                    $j = $row['job'];
                    $cats = $row['additional_by_category'] ?? [];
                    fputcsv($out, [
                        $j->job_number,
                        $j->product?->name ?? '',
                        $row['fabric'],
                        $row['accessory'],
                        $cats['transport']['amount'] ?? 0,
                        $cats['sewing']['amount'] ?? 0,
                        $cats['washing']['amount'] ?? 0,
                        $row['additional_total'] - (($cats['transport']['amount'] ?? 0) + ($cats['sewing']['amount'] ?? 0) + ($cats['washing']['amount'] ?? 0)),
                        $row['total'],
                        $row['cost_per_unit'],
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['=== ALL ADDITIONAL COST LINES ===']);
                fputcsv($out, ['Job #', 'Category', 'Description', 'Amount (Rs)', 'Date']);
                foreach ($data['additional_lines'] ?? [] as $line) {
                    fputcsv($out, [
                        $line['job_number'],
                        $line['category_label'],
                        $line['description'] ?? '',
                        $line['amount'],
                        $line['created_at']?->format('Y-m-d') ?? '',
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['=== RECIPE PLANNED OTHER COSTS ===']);
                fputcsv($out, ['Recipe', 'Product', 'Description', 'Amount (Rs)']);
                foreach ($data['recipe_other_lines'] ?? [] as $line) {
                    fputcsv($out, [
                        $line->recipe?->name ?? '',
                        $line->recipe?->product?->name ?? '',
                        $line->description,
                        $line->amount,
                    ]);
                }
                break;
            case 'business-summary':
                fputcsv($out, ['Business Summary', $from . ' to ' . $to]);
                fputcsv($out, []);
                fputcsv($out, ['SALES']);
                fputcsv($out, ['Total Sales (Rs)', $data['sales']['total'] ?? 0]);
                fputcsv($out, ['Orders', $data['sales']['orders'] ?? 0]);
                fputcsv($out, ['Collected (Rs)', $data['sales']['collected'] ?? 0]);
                fputcsv($out, ['Outstanding (Rs)', $data['sales']['outstanding'] ?? 0]);
                fputcsv($out, []);
                fputcsv($out, ['PURCHASES']);
                fputcsv($out, ['Total Purchases (Rs)', $data['purchases']['total'] ?? 0]);
                fputcsv($out, ['Paid (Rs)', $data['purchases']['paid'] ?? 0]);
                fputcsv($out, []);
                fputcsv($out, ['EXPENSES']);
                fputcsv($out, ['Total Expenses (Rs)', $data['expenses']['total'] ?? 0]);
                foreach ($data['expenses']['by_category'] ?? [] as $cat => $amt) {
                    fputcsv($out, [$cat, $amt]);
                }
                fputcsv($out, []);
                fputcsv($out, ['PRODUCTION (Jobs)']);
                fputcsv($out, ['Jobs', $data['production']['job_count'] ?? 0]);
                fputcsv($out, ['Total Production Cost (Rs)', $data['production']['total_cost'] ?? 0]);
                fputcsv($out, ['Fabric (Rs)', $data['production']['fabric'] ?? 0]);
                fputcsv($out, ['Accessory (Rs)', $data['production']['accessory'] ?? 0]);
                fputcsv($out, ['Transport (Rs)', $data['production']['transport'] ?? 0]);
                fputcsv($out, ['Sewing (Rs)', $data['production']['sewing'] ?? 0]);
                fputcsv($out, ['Washing (Rs)', $data['production']['washing'] ?? 0]);
                fputcsv($out, ['Other Added Costs (Rs)', $data['production']['additional'] ?? 0]);
                fputcsv($out, []);
                fputcsv($out, ['ESTIMATED MARGIN (Rs)', $data['summary']['estimated_margin'] ?? 0]);
                break;
            case 'cost-report':
                fputcsv($out, ['Cost Report / Cost Sheet', $from . ' to ' . $to]);
                fputcsv($out, []);
                $avg = $data['simple_average'] ?? [];
                fputcsv($out, ['SIMPLE AVERAGE (Expenses ÷ pieces)']);
                fputcsv($out, ['Total Expenses (Rs)', $avg['total_expenses'] ?? 0]);
                fputcsv($out, ['Finished pieces', $avg['finished_pieces'] ?? 0]);
                fputcsv($out, ['Overhead per piece (Rs)', $avg['overhead_per_piece'] ?? 0]);
                fputcsv($out, ['Category', 'Amount (Rs)']);
                foreach ($avg['expense_lines'] ?? [] as $line) {
                    fputcsv($out, [$line['label'] ?? '', $line['amount'] ?? 0]);
                }
                fputcsv($out, []);
                fputcsv($out, ['REVENUE (Collections basis)']);
                fputcsv($out, ['Collections (Rs)', $data['revenue']['collections'] ?? 0]);
                fputcsv($out, ['Refunds (Rs)', $data['revenue']['refunds'] ?? 0]);
                fputcsv($out, ['Net Revenue (Rs)', $data['revenue']['net'] ?? 0]);
                fputcsv($out, ['Orders', $data['revenue']['orders'] ?? 0]);
                fputcsv($out, []);
                foreach ($data['topics'] ?? [] as $topic) {
                    fputcsv($out, [$topic['label'] ?? '', 'Total (Rs)', $topic['total'] ?? 0]);
                    fputcsv($out, ['Line', 'Source', 'Amount (Rs)']);
                    foreach ($topic['lines'] ?? [] as $line) {
                        fputcsv($out, [$line['label'] ?? '', $line['source'] ?? '', $line['amount'] ?? 0]);
                    }
                    fputcsv($out, []);
                }
                fputcsv($out, ['TOTAL COSTS (Rs)', $data['summary']['total_costs'] ?? 0]);
                fputcsv($out, ['NET PROFIT (Rs)', $data['summary']['net_profit'] ?? 0]);
                break;
            case 'cost-analyst':
                fputcsv($out, ['Cost Analyst Report', $from . ' to ' . $to]);
                fputcsv($out, []);
                fputcsv($out, ['SUMMARY']);
                fputcsv($out, ['Net Revenue (Rs)', $data['revenue']['net'] ?? 0]);
                fputcsv($out, ['Ledger total filtered (Rs)', $data['summary']['ledger_total'] ?? 0]);
                fputcsv($out, ['Cost sheet total (Rs)', $data['summary']['total_costs'] ?? 0]);
                fputcsv($out, ['Net Profit (Rs)', $data['summary']['net_profit'] ?? 0]);
                fputcsv($out, []);
                fputcsv($out, ['BY SOURCE', 'Lines', 'Amount (Rs)']);
                foreach ($data['source_totals'] ?? [] as $row) {
                    fputcsv($out, [$row['label'] ?? '', $row['count'] ?? 0, $row['total'] ?? 0]);
                }
                fputcsv($out, []);
                fputcsv($out, ['ALL LINES']);
                fputcsv($out, ['Date', 'Source', 'Category', 'Topic', 'Description', 'Reference', 'Person', 'Amount (Rs)']);
                foreach ($data['ledger'] ?? [] as $line) {
                    fputcsv($out, [
                        $line['date'] ?? '',
                        $line['source'] ?? '',
                        $line['category'] ?? '',
                        $line['topic_label'] ?? '',
                        $line['description'] ?? '',
                        $line['reference'] ?? '',
                        $line['person'] ?? '',
                        $line['amount'] ?? 0,
                    ]);
                }
                break;
            case 'product-final-cost':
                fputcsv($out, ['Product Final Cost Report', $from . ' to ' . $to]);
                fputcsv($out, ['Job #', 'Product', 'Recipe', 'Output', 'Planned/pc', 'Actual/pc', 'Variance/pc', 'Profit/pc', 'Status']);
                foreach ($data['rows'] ?? [] as $row) {
                    fputcsv($out, [
                        $row['job']->job_number ?? '',
                        $row['product']?->name ?? '',
                        $row['planned']['recipe_name'] ?? '',
                        $row['output_qty'] ?? 0,
                        $row['planned']['per_unit'] ?? 0,
                        $row['actual']['per_unit'] ?? 0,
                        $row['variance']['per_unit'] ?? 0,
                        $row['selling']['profit_per_unit_actual'] ?? '',
                        ($row['is_converted_to_product'] ?? false) ? 'Converted' : 'Final check',
                    ]);
                }
                break;
            default:
                fputcsv($out, ['Report', 'No CSV export defined for this report.']);
        }
    }

    protected function exportPdf(string $report, string $title, array $data, string $from, string $to): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('erp.reports.pdf', [
            'report' => $report,
            'title' => $title,
            'data' => $data,
            'from' => $from,
            'to' => $to,
        ]);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download(str_replace(' ', '-', $title) . '-' . $from . '.pdf');
    }

    // -------------------------------------------------------------------------
    // Product Stock Report
    // -------------------------------------------------------------------------
    protected function showProductStockReport(Request $request)
    {
        $categoryId = $request->get('category_id');
        $productType = $request->get('product_type');
        $showZero = (bool) $request->get('show_zero', false);
        $search = trim((string) $request->get('q', ''));

        $categories = Category::orderBy('name')->get(['id', 'name']);

        // Aggregate stock from ledger per product & variation
        $stockByProduct = StockLedger::query()
            ->selectRaw('product_id, COALESCE(SUM(quantity), 0) as total_stock')
            ->groupBy('product_id')
            ->pluck('total_stock', 'product_id');

        $stockByVariation = StockLedger::query()
            ->whereNotNull('product_variation_id')
            ->selectRaw('product_id, product_variation_id, COALESCE(SUM(quantity), 0) as total_stock')
            ->groupBy('product_id', 'product_variation_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->pluck('total_stock', 'product_variation_id'));

        $productsQuery = Product::query()
            ->with([
                'category',
                'variations:id,product_id,variation_name,sub_sku,default_purchase_price,wholesale_price,default_sell_price',
            ])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($productType, fn ($q) => $q->where('product_type', $productType))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->orderBy('sku');

        $products = $productsQuery->get();

        $recipeCostsByProduct = ProductRecipe::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->orderByDesc('id')
            ->get(['product_id', 'production_cost'])
            ->unique('product_id')
            ->pluck('production_cost', 'product_id');

        // Attach stock info
        $rows = $products->map(function (Product $product) use ($stockByProduct, $stockByVariation, $recipeCostsByProduct) {
            $totalStock = (float) ($stockByProduct[$product->id] ?? 0);
            $varStocks = $stockByVariation[$product->id] ?? collect();

            $manualCost = (float) $product->cost_price;
            $recipeCost = (float) ($recipeCostsByProduct[$product->id] ?? 0);

            $variations = $product->variations->map(function ($v) use ($varStocks, $manualCost, $recipeCost) {
                $varCost = (float) $v->default_purchase_price;

                return [
                    'id' => $v->id,
                    'name' => $v->variation_name,
                    'sub_sku' => $v->sub_sku,
                    'cost_price' => $varCost,
                    'wholesale_price' => (float) $v->wholesale_price,
                    'sell_price' => (float) $v->default_sell_price,
                    'stock' => (float) ($varStocks[$v->id] ?? 0),
                    'unit_cost' => $varCost > 0 ? $varCost : ($manualCost > 0 ? $manualCost : $recipeCost),
                ];
            });

            $unitCost = $manualCost;
            $costSource = '—';

            if ($manualCost > 0) {
                $costSource = 'Manual';
            } elseif ($recipeCost > 0) {
                $unitCost = $recipeCost;
                $costSource = 'Recipe';
            } elseif ($product->product_type === 'variable') {
                $varCosts = $variations->pluck('cost_price')->filter(fn ($c) => $c > 0);
                if ($varCosts->isNotEmpty()) {
                    $unitCost = (float) $varCosts->avg();
                    $costSource = 'Variation';
                }
            }

            if ($product->product_type === 'variable' && $variations->isNotEmpty()) {
                $lineValue = $variations->sum(fn ($v) => $v['stock'] * $v['unit_cost']);
            } else {
                $lineValue = $totalStock * $unitCost;
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category?->name ?? '—',
                'product_type' => $product->product_type,
                'cost_price' => $unitCost,
                'manual_cost' => $manualCost,
                'recipe_cost' => $recipeCost,
                'cost_source' => $costSource,
                'line_value' => round($lineValue, 2),
                'selling_price' => (float) $product->selling_price,
                'wholesale_price' => (float) $product->wholesale_price,
                'total_stock' => $totalStock,
                'variations' => $variations,
                'is_active' => $product->is_active,
            ];
        });

        if (!$showZero) {
            $rows = $rows->filter(fn ($r) => $r['total_stock'] != 0);
        }

        $summary = [
            'total_products' => $rows->count(),
            'total_stock_qty' => $rows->sum('total_stock'),
            'stock_value' => round($rows->sum('line_value'), 2),
        ];

        return view('erp.reports.product-stock', compact(
            'rows', 'categories', 'summary',
            'categoryId', 'productType', 'showZero', 'search'
        ));
    }

    // -------------------------------------------------------------------------
    // Orders Analysis Report
    // -------------------------------------------------------------------------
    protected function showOrdersAnalysisReport(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $salesPersonId = $request->get('sales_person_id');
        $customerId = $request->get('customer_id');
        $status = $request->get('status');
        $search = trim((string) $request->get('q', ''));

        $salesPersons = User::where('role', 'sales_person')
            ->orderBy('name')->get(['id', 'name']);

        $ordersQuery = Order::query()
            ->countedInSales()
            ->with(['customer:id,name,business_name,phone', 'salesPerson:id,name', 'payments:order_id,amount'])
            ->whereBetween(DB::raw('DATE(COALESCE(order_date, created_at))'), [$from, $to])
            ->when($salesPersonId, fn ($q) => $q->where('user_id', $salesPersonId))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('order_number', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('business_name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw('COALESCE(order_date, created_at) DESC');

        $orders = $ordersQuery->get();

        $rows = $orders->map(function (Order $order) {
            $paid = $order->payments->sum('amount');
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'invoice_number' => $order->invoice_number,
                'order_date' => $order->order_date?->format('Y-m-d') ?? $order->created_at->format('Y-m-d'),
                'customer' => $order->customer?->display_name ?? '—',
                'customer_phone' => $order->customer?->phone ?? '',
                'sales_person' => $order->salesPerson?->name ?? '—',
                'total_amount' => $order->invoice_total,
                'paid_amount' => (float) $paid,
                'due_amount' => max(0, $order->invoice_total - (float) $paid),
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
            ];
        });

        $summary = [
            'total_orders' => $rows->count(),
            'total_revenue' => $rows->sum('total_amount'),
            'total_collected' => $rows->sum('paid_amount'),
            'total_due' => $rows->sum('due_amount'),
        ];

        // For CSV export
        if ($request->get('format') === 'csv') {
            $filename = 'orders-analysis-' . $from . '-to-' . $to . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Order #', 'Invoice #', 'Date', 'Customer', 'Phone', 'Sales Person', 'Total (Rs)', 'Paid (Rs)', 'Due (Rs)', 'Status']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['order_number'], $r['invoice_number'], $r['order_date'],
                        $r['customer'], $r['customer_phone'], $r['sales_person'],
                        $r['total_amount'], $r['paid_amount'], $r['due_amount'], $r['status_label'],
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $allStatuses = Order::adminBoardStatuses();
        $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")->get(['id', 'name', 'business_name']);

        return view('erp.reports.orders-analysis', compact(
            'rows', 'summary', 'from', 'to',
            'salesPersons', 'customers', 'allStatuses',
            'salesPersonId', 'customerId', 'status', 'search'
        ));
    }

    protected function showOrdersManufacturingReport(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $data = app(\App\Services\OrdersManufacturingReportService::class)->build($from, $to, $request);
        $title = $this->getReportTitle('orders-manufacturing');

        return view('erp.reports.orders-manufacturing', compact('data', 'title', 'from', 'to'));
    }

    protected function exportOrdersManufacturingCsv(Request $request): StreamedResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $data = app(\App\Services\OrdersManufacturingReportService::class)->build($from, $to, $request);
        $filename = 'orders-manufacturing-' . $from . '-to-' . $to . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Order #', 'Date', 'Customer', 'Phone', 'Sales Person', 'Status',
                'Order Mfg Required', 'Product', 'SKU', 'Variation',
                'Order Qty', 'Stock', 'Make Qty', 'Line Mfg Required',
            ]);
            foreach ($data['orders'] as $order) {
                foreach ($order['lines'] as $line) {
                    fputcsv($out, [
                        $order['order_number'],
                        $order['order_date'],
                        $order['customer'],
                        $order['customer_phone'] ?? '',
                        $order['sales_person'],
                        $order['status_label'],
                        $order['manufacturing_label'],
                        $line['product_name'],
                        $line['product_sku'] ?? '',
                        $line['variation_name'],
                        $line['ordered_qty'],
                        $line['available_stock'],
                        $line['shortage_qty'],
                        $line['manufacturing_label'],
                    ]);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // -------------------------------------------------------------------------
    // Inventory Report (raw materials / purchase items)
    // -------------------------------------------------------------------------
    protected function showInventoryReport(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $type = $request->get('type', 'all');
        $stockFilter = $request->get('stock_filter', 'all');
        $search = trim((string) $request->get('q', ''));
        $sort = $request->get('sort', 'name');
        $lowStockThreshold = 10;

        $materialsQuery = Material::query()
            ->when(in_array($type, ['fabric', 'accessory'], true), fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->when($stockFilter === 'in_stock', fn ($q) => $q->where('quantity', '>', 0))
            ->when($stockFilter === 'zero', fn ($q) => $q->where('quantity', '<=', 0))
            ->when($stockFilter === 'low', fn ($q) => $q->where('quantity', '>', 0)->where('quantity', '<=', $lowStockThreshold));

        $materials = $materialsQuery->get(['id', 'name', 'type', 'unit', 'quantity', 'unit_price']);

        $materialIds = $materials->pluck('id');

        $movementRows = collect();
        $lastMovementDates = collect();

        if ($materialIds->isNotEmpty()) {
            $movementRows = MaterialStockMovement::query()
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->whereIn('material_id', $materialIds)
                ->selectRaw('material_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) as qty_in')
                ->selectRaw('COALESCE(SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END), 0) as qty_out')
                ->selectRaw('COALESCE(SUM(quantity), 0) as net_qty')
                ->groupBy('material_id')
                ->get()
                ->keyBy('material_id');

            $lastMovementDates = MaterialStockMovement::query()
                ->whereIn('material_id', $materialIds)
                ->selectRaw('material_id, MAX(created_at) as last_at')
                ->groupBy('material_id')
                ->pluck('last_at', 'material_id');
        }

        $rows = $materials->map(function (Material $material) use ($movementRows, $lastMovementDates, $lowStockThreshold) {
            $qty = (float) $material->quantity;
            $unitPrice = (float) ($material->unit_price ?? 0);
            $lineValue = round($qty * $unitPrice, 2);
            $movement = $movementRows->get($material->id);

            $status = 'ok';
            if ($qty <= 0) {
                $status = 'out';
            } elseif ($qty <= $lowStockThreshold) {
                $status = 'low';
            }

            return [
                'id' => $material->id,
                'name' => $material->name,
                'type' => $material->type,
                'type_label' => ucfirst((string) $material->type),
                'unit' => $material->unit ?: '—',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_value' => $lineValue,
                'status' => $status,
                'qty_in' => (float) ($movement->qty_in ?? 0),
                'qty_out' => (float) ($movement->qty_out ?? 0),
                'net_qty' => (float) ($movement->net_qty ?? 0),
                'last_movement_at' => $lastMovementDates[$material->id] ?? null,
            ];
        });

        $rows = match ($sort) {
            'qty_desc' => $rows->sortByDesc('quantity')->values(),
            'qty_asc' => $rows->sortBy('quantity')->values(),
            'value_desc' => $rows->sortByDesc('line_value')->values(),
            'type' => $rows->sortBy(['type', 'name'])->values(),
            default => $rows->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };

        $summary = [
            'total_items' => $rows->count(),
            'fabric_count' => $rows->where('type', 'fabric')->count(),
            'accessory_count' => $rows->where('type', 'accessory')->count(),
            'total_qty' => round($rows->sum('quantity'), 2),
            'stock_value' => round($rows->sum('line_value'), 2),
            'fabric_value' => round($rows->where('type', 'fabric')->sum('line_value'), 2),
            'accessory_value' => round($rows->where('type', 'accessory')->sum('line_value'), 2),
            'low_stock_count' => $rows->where('status', 'low')->count(),
            'out_of_stock_count' => $rows->where('status', 'out')->count(),
            'period_in' => round($rows->sum('qty_in'), 2),
            'period_out' => round($rows->sum('qty_out'), 2),
        ];

        if (in_array($request->get('format'), ['csv', 'excel'], true)) {
            $filename = 'inventory-report-' . $from . '-to-' . $to . '.csv';

            return response()->streamDownload(function () use ($rows, $from, $to) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Inventory Report', $from, 'to', $to]);
                fputcsv($out, []);
                fputcsv($out, [
                    'Item', 'Type', 'Unit', 'Current Qty', 'Unit Price (Rs)', 'Stock Value (Rs)',
                    'Status', 'Period In', 'Period Out', 'Period Net', 'Last Movement',
                ]);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['name'],
                        $r['type_label'],
                        $r['unit'],
                        $r['quantity'],
                        $r['unit_price'],
                        $r['line_value'],
                        strtoupper($r['status']),
                        $r['qty_in'],
                        $r['qty_out'],
                        $r['net_qty'],
                        $r['last_movement_at'] ? \Carbon\Carbon::parse($r['last_movement_at'])->format('Y-m-d H:i') : '',
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('erp.reports.inventory', compact(
            'rows',
            'summary',
            'from',
            'to',
            'type',
            'stockFilter',
            'search',
            'sort',
            'lowStockThreshold'
        ));
    }

    /** @return array{0: string, 1: string, 2: string} [from, to, selectedMonth YYYY-MM] */
    protected function resolveReportDates(Request $request): array
    {
        $month = (string) $request->get('month', '');
        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::parse($month . '-01');

            return [
                $start->copy()->startOfMonth()->toDateString(),
                $start->copy()->endOfMonth()->toDateString(),
                $month,
            ];
        }

        $from = (string) $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = (string) $request->get('to', now()->format('Y-m-d'));
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to, $this->monthKeyForRange($from, $to)];
    }

    protected function monthKeyForRange(string $from, string $to): string
    {
        if (substr($from, 0, 7) === substr($to, 0, 7)
            && $from === Carbon::parse(substr($from, 0, 7) . '-01')->startOfMonth()->toDateString()
            && $to === Carbon::parse(substr($to, 0, 7) . '-01')->endOfMonth()->toDateString()) {
            return substr($from, 0, 7);
        }

        return substr($from, 0, 7);
    }
}

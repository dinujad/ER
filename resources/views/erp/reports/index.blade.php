@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Reports</h4>
    </div>

    {{-- Advanced Reports --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header" style="background:linear-gradient(90deg,#1e3a5f,#2d6a9f); color:#fff; border-radius:12px 12px 0 0;">
            <h6 class="mb-0"><i class="bi bi-stars me-2"></i>Advanced Reports</h6>
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'product-stock') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-box-seam me-3 fs-5 text-primary"></i>
                    <span>
                        <strong>Product Stock Report</strong>
                        <br><small class="text-muted">Every product's stock — size-wise breakdown, cost value, filter by category / type</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'inventory') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-boxes me-3 fs-5 text-success"></i>
                    <span>
                        <strong>Inventory Report</strong>
                        <br><small class="text-muted">Raw materials — fabric &amp; accessories stock, value, low/out alerts, period in/out movements</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'orders-manufacturing') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-gear me-3 fs-5 text-warning"></i>
                    <span>
                        <strong>Orders Manufacturing Report</strong>
                        <br><small class="text-muted">Open orders — order number, stock vs qty, manufacture කරන්න ඕනද, CSV export</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'orders-analysis') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-cart-check me-3 fs-5 text-success"></i>
                    <span>
                        <strong>Orders Analysis Report</strong>
                        <br><small class="text-muted">All orders — filter by sales person, customer, status, date. Revenue / collected / outstanding KPIs + CSV export</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'trending-products') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-graph-up-arrow me-3 fs-5 text-danger"></i>
                    <span>
                        <strong>Trending Products Report</strong>
                        <br><small class="text-muted">Best sellers · variation breakdown · who bought · E media AI agent price recommendation</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'pending-cheques') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-hourglass-split me-3 fs-5 text-warning"></i>
                    <span>
                        <strong>Pending Cheques Report</strong>
                        <br><small class="text-muted">Wholesale cheques received but not deposited to bank — by customer business name, days pending, amounts</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'sales-person-expenses') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-receipt-cutoff me-3 fs-5 text-primary"></i>
                    <span>
                        <strong>Sales Person Expenses Report</strong>
                        <br><small class="text-muted">Field expenses by sales person — type, route, receipts, date range, PDF / CSV</small>
                    </span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
        </ul>
    </div>

    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary bg-opacity-10">
            <h6 class="mb-0 text-primary"><i class="bi bi-pie-chart me-2"></i>Analysis Reports</h6>
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'business-summary') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-clipboard-data me-3 fs-5 text-primary"></i>
                    <span><strong>Business Summary Report</strong><br><small class="text-muted">Final overview — Sales, Purchases, Expenses, Production (Transport, Sewing, Washing), Margin</small></span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            @if(auth()->user()?->can_access('manufacturing.costs'))
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'product-final-cost') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-clipboard-check me-3 fs-5 text-primary"></i>
                    <span><strong>Product Final Cost Report</strong><br><small class="text-muted">Recipe planned vs actual cost when product is finished — variance &amp; profit per piece</small></span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'cost-analyst') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-search me-3 fs-5 text-info"></i>
                    <span><strong>Cost Analyst Report</strong><br><small class="text-muted">All system expenses — ERP, payroll, field, vehicle, production — date range filter + Net Profit</small></span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'cost-report') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-journal-text me-3 fs-5 text-danger"></i>
                    <span><strong>Cost Report / Cost Sheet</strong><br><small class="text-muted">Direct, Utilities, Maintenance, Packing/Logistics, Indirect Labor, Admin Overheads → Net Profit</small></span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'cost-analysis') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-currency-exchange me-3 fs-5 text-warning"></i>
                    <span><strong>Production Cost Analysis</strong><br><small class="text-muted">Transport, Sewing, Washing &amp; other costs — job-wise + recipe planned costs</small></span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
            @endif
            <li class="list-group-item list-group-item-action">
                <a href="{{ route('reports.show', 'profit-loss') }}" class="text-decoration-none text-dark d-flex align-items-center">
                    <i class="bi bi-graph-up-arrow me-3 fs-5 text-success"></i>
                    <span><strong>Profit / Loss Report</strong><br><small class="text-muted">Revenue vs costs summary for the period</small></span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </li>
        </ul>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Operational Reports</h6>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'manufacturing') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-gear-wide-connected me-3 fs-5 text-primary"></i>
                        <span><strong>Manufacturing Report</strong><br><small class="text-muted">Jobs, hourly output, washing, QC — filters &amp; PDF export</small></span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'purchase') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-cart-plus me-3 fs-5"></i>
                        <span>Purchase Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'sale') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-cart-check me-3 fs-5"></i>
                        <span>Sale Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'supplier') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-truck me-3 fs-5"></i>
                        <span>Supplier Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'customer') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-people me-3 fs-5"></i>
                        <span>Customer Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'stock') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-box-seam me-3 fs-5"></i>
                        <span>Stock Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'stock-adjustment') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-sliders me-3 fs-5"></i>
                        <span>Stock Adjustment Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'expense') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-wallet2 me-3 fs-5"></i>
                        <span><strong>Unified Expense Report</strong><br><small class="text-muted">All system expenses — ERP, sales field, purchase payments, payroll, vehicle, production — linked to Accounting</small></span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'purchase-payment') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-cash-stack me-3 fs-5"></i>
                        <span>Purchase Payment Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'sell-payment') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-credit-card me-3 fs-5"></i>
                        <span>Sell Payment Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'product-sell') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-cart me-3 fs-5"></i>
                        <span>Product Sell Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'sales-representative') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-person-lines-fill me-3 fs-5"></i>
                        <span>Sales Person Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'sales-person-expenses') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-receipt-cutoff me-3 fs-5"></i>
                        <span>Sales Person Expenses Report</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
                <li class="list-group-item list-group-item-action">
                    <a href="{{ route('reports.show', 'activity-log') }}" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="bi bi-clock-history me-3 fs-5"></i>
                        <span>Activity Log</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

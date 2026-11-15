@extends('erp.layout')

@section('title', 'Orders')

@section('content')
<style>
    /* Form wraps header/body/footer — Bootstrap modal-dialog-scrollable needs this */
    [id^="issueInvoiceModal"] .modal-dialog {
        max-height: calc(100vh - 1rem);
        margin: 0.5rem auto;
    }
    [id^="issueInvoiceModal"] .modal-content,
    [id^="issueInvoiceModal"] .modal-content > form {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 1rem);
        overflow: hidden;
    }
    [id^="issueInvoiceModal"] .modal-body {
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        flex: 1 1 auto;
        min-height: 0;
    }
    [id^="issueInvoiceModal"] .modal-header,
    [id^="issueInvoiceModal"] .modal-footer {
        flex-shrink: 0;
    }
    /* Scoped UI styling for orders */
    .orders-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        background: #fff;
    }
    .orders-header {
        padding: 1.25rem 1.5rem;
        background: transparent;
        border-bottom: 1px solid #f1f5f9;
    }
    .orders-title {
        font-weight: 700;
        font-size: 1.25rem;
        color: #1e293b;
        margin: 0;
    }
    /* Filter pills */
    .filter-pills .btn {
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 0.4rem 1rem;
        border: 1px solid #e2e8f0;
        color: #64748b;
        background: #fff;
        transition: all 0.2s;
    }
    .filter-pills .btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    .filter-pills .btn.active-pill {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    }

    /* Select2: match Bootstrap form-select-sm in filter bar */
    .orders-card .select2-container--default .select2-selection--single {
        height: calc(1.5em + 0.5rem + 2px);
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    .orders-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + 0.5rem);
        padding-left: 0.5rem;
        color: #212529;
    }
    .orders-card .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + 0.5rem);
    }
    
    /* Table styling */
    .table-custom {
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    .table-custom thead th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .table-custom tbody td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 500;
    }
    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }
    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Soft Badges */
    .s-badge {
        padding: 0.4em 0.7em;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .s-badge-success { background: #dcfce7; color: #166534; }
    .s-badge-warning { background: #fef3c7; color: #92400e; }
    .s-badge-secondary { background: #f1f5f9; color: #475569; }
    .s-badge-info { background: #e0f2fe; color: #075985; }
    .s-badge-dark { background: #e2e8f0; color: #1e293b; }
    .s-badge-primary { background: #dbeafe; color: #1e40af; }
    .s-badge-danger { background: #fee2e2; color: #991b1b; }
    
    /* Action Buttons */
    .action-btn {
        padding: 0.35rem 0.6rem;
        font-size: 0.8rem;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
    }
    .action-view { background: #e0f2fe; color: #0284c7; }
    .action-view:hover { background: #bae6fd; color: #0369a1; }
    
    .action-pay { background: #dcfce7; color: #15803d; }
    .action-pay:hover { background: #bbf7d0; color: #166534; }
    
    .action-inv { background: #f1f5f9; color: #475569; }
    .action-inv:hover { background: #e2e8f0; color: #334155; }

    .action-delete { background: #fee2e2; color: #b91c1c; }
    .action-delete:hover { background: #fecaca; color: #991b1b; }
    
    .btn-create-order {
        background: #3b82f6;
        color: #fff;
        border-radius: 8px;
        font-weight: 500;
        padding: 0.5rem 1.25rem;
        border: none;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-create-order:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
        color: #fff;
    }
    .manufacturing-needed-row {
        background: #fff7ed !important;
    }
    .manufacturing-needed-row:hover {
        background: #ffedd5 !important;
    }
    .status-board-section {
        border-top: 3px solid var(--status-accent, #94a3b8);
        margin: 0 1rem 1.25rem;
        border-radius: 10px;
        background: #fafbfc;
        overflow: hidden;
    }
    .status-board-section .status-board-head {
        padding: 0.75rem 1rem;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .status-board-section .status-board-head h4 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
    }
    .status-nav-pills .btn {
        border-radius: 20px;
        font-size: 0.8rem;
        padding: 0.35rem 0.85rem;
    }
    .status-nav-pills .status-tab-btn.active {
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        filter: saturate(1.1);
    }
    .status-nav-pills .status-tab-btn.btn-outline-warning.active { background: #f59e0b; color: #fff; border-color: #f59e0b; }
    .status-nav-pills .status-tab-btn.btn-outline-info.active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
    .status-nav-pills .status-tab-btn.btn-outline-primary.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
    .status-nav-pills .status-tab-btn.btn-outline-success.active { background: #22c55e; color: #fff; border-color: #22c55e; }
    .status-nav-pills .status-tab-btn.btn-outline-danger.active { background: #ef4444; color: #fff; border-color: #ef4444; }
    .status-board-section {
        display: none;
    }
    .status-board-section.is-active {
        display: block;
    }
    .status-move-btns .btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.45rem;
        white-space: nowrap;
    }
</style>

<div class="card orders-card">
    <div class="card-header orders-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <h3 class="orders-title"><i class="bi bi-cart3 me-2 text-primary"></i>{{ !empty($urgentOnly) ? 'Urgent Orders' : 'Orders' }}</h3>
            
            <div class="filter-pills d-flex flex-wrap gap-2">
                @if(!in_array(($workflow ?? 'all'), ['ac', 'issued']))
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'type'), ['type' => 'all'])) }}" class="btn {{ ($type ?? 'all') === 'all' ? 'active-pill' : '' }}">All</a>
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'type'), ['type' => 'retail'])) }}" class="btn {{ ($type ?? '') === 'retail' ? 'active-pill' : '' }}">Retail</a>
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'type'), ['type' => 'wholesale'])) }}" class="btn {{ ($type ?? '') === 'wholesale' ? 'active-pill' : '' }}">Wholesale</a>
                @endif
                <a href="{{ route('orders.index', array_merge(request()->except('page', 'workflow'), ['workflow' => 'ac'])) }}" class="btn {{ ($workflow ?? '') === 'ac' ? 'active-pill' : '' }}">Order AC</a>
                <a href="{{ route('orders.index', array_merge(request()->except('page', 'workflow'), ['workflow' => 'issued'])) }}" class="btn {{ ($workflow ?? '') === 'issued' ? 'active-pill' : '' }}">Issued Invoices</a>
            </div>
            
            @if(isset($type) && $type !== 'all')
                <div class="text-muted small fw-medium">
                    Showing: {{ $type === 'wholesale' ? 'Sales Person Orders' : 'Walk-in / Retail Orders' }}
                </div>
            @endif
        </div>
        
        <a href="{{ route('orders.create') }}" class="btn btn-create-order">
            <i class="bi bi-plus-lg me-1"></i> Create Order
        </a>
    </div>
    
    <div class="card-body p-0">
        <div class="p-3 border-bottom bg-light">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="type" value="{{ $type ?? 'all' }}">
                <input type="hidden" name="workflow" value="{{ $workflow ?? 'all' }}">
                @if(!empty($urgentOnly))
                    <input type="hidden" name="urgent" value="1">
                @endif

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Order No</label>
                    <input type="text" name="order_no" class="form-control form-control-sm" value="{{ request('order_no') }}" placeholder="ORD-...">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'afterchecking' => 'After Checking', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Sales Person</label>
                    <select name="sales_person_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($salesPersons as $sp)
                            <option value="{{ $sp->id }}" {{ (string) request('sales_person_id') === (string) $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1" for="orders_filter_customer_id">Customer</label>
                    <select name="customer_id" id="orders_filter_customer_id" class="form-select form-select-sm orders-customer-filter-select2">
                        <option value="">All</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->display_name }}@if(filled($customer->phone)) ({{ $customer->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Paid / Due</label>
                    <select name="payment_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="paid" {{ request('payment_state') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="due" {{ request('payment_state') === 'due' ? 'selected' : '' }}>Due</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">නිෂ්පාදනය</label>
                    <select name="manufacturing_state" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="needed" {{ request('manufacturing_state') === 'needed' ? 'selected' : '' }}>අවශ්‍යයි</option>
                        <option value="not_needed" {{ request('manufacturing_state') === 'not_needed' ? 'selected' : '' }}>අවශ්‍ය නැහැ</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('orders.index', array_filter(['type' => $type ?? 'all', 'workflow' => ($workflow ?? 'all') !== 'all' ? $workflow : null, 'urgent' => !empty($urgentOnly) ? 1 : null])) }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>

        @if(!empty($manufacturingNeededOrderIds) && !in_array(($workflow ?? 'all'), ['ac', 'issued']))
            <div class="alert alert-warning m-3 mb-0 border border-warning-subtle">
                <strong>Stock නැත / නිෂ්පාදනය අවශ්‍ය:</strong> available stock අඩු orders
                <strong>{{ count($manufacturingNeededOrderIds) }}</strong> ක්. Highlight කරලා තියෙන rows බලන්න.
            </div>
        @endif
        @include('erp.orders.partials.status-board')
    </div>
</div>

@foreach($orders as $order)
    @if($order->status === 'afterchecking')
        @php
            $modalSubtotal = (float) $order->items->sum('total_price');
        @endphp
        <div class="modal fade" id="issueInvoiceModal{{ $order->id }}" tabindex="-1" aria-labelledby="issueInvoiceModalLabel{{ $order->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('orders.issue_invoice', $order->id) }}" class="issue-invoice-form" data-modal-order-id="{{ $order->id }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="issueInvoiceModalLabel{{ $order->id }}">Issue Invoice - {{ $order->order_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2"><strong>Customer (Business):</strong> {{ $order->customer->display_name ?? 'N/A' }}</div>
                            @if($order->customer?->name && $order->customer?->business_name && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
                                <div class="mb-3 text-muted small"><strong>Contact:</strong> {{ $order->customer->name }}</div>
                            @endif
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Variation</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product->name ?? 'Product' }}</td>
                                                <td>{{ $item->variation->variation_name ?? '-' }}</td>
                                                <td class="text-end">{{ number_format((float)$item->quantity, 2) }}</td>
                                                <td class="text-end">{{ number_format((float)$item->unit_price, 2) }}</td>
                                                <td class="text-end">{{ number_format((float)$item->total_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @include('erp.orders.partials.invoice-financial-fields', [
                                'order' => $order,
                                'subtotal' => $modalSubtotal,
                            ])
</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Issue Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

@include('erp.orders.partials.invoice-financial-script')
@endsection

@section('scripts')
<script>
    jQuery(function ($) {
        if (!$.fn.select2) {
            return;
        }
        $('#orders_filter_customer_id').select2({
            placeholder: 'All customers',
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
        });
    });
</script>
@endsection

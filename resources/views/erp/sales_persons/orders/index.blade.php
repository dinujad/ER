@extends('sales_person.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ !empty($showingDrafts) ? 'Draft Orders' : 'My Orders' }}</h1>
        <a href="{{ route('sales-person.orders.create') }}" class="btn btn-primary">New Order</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Order List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->customer?->display_name ?? 'N/A' }}</td>
                            <td>{{ $order->order_date?->format('Y-m-d') ?? $order->created_at->format('Y-m-d') }}</td>
                            <td>{{ number_format($order->invoice_total, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $order->status == 'completed' ? 'success' : ($order->status == 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                @if($order->status === 'draft')
                                    <a href="{{ route('sales-person.orders.create', ['draft' => $order->id]) }}" class="btn btn-sm btn-primary">Edit</a>
                                @else
                                    <a href="{{ route('sales-person.orders.show', $order->id) }}" class="btn btn-sm btn-info">View</a>
                                    @if((int) $order->user_id === (int) auth()->id() && $order->canSalesPersonEdit())
                                        <a href="{{ route('sales-person.orders.edit', $order) }}" class="btn btn-sm btn-warning">Edit</a>
                                    @endif
                                    @if($order->canAcceptPayments() && $order->due_amount > 0)
                                        <a href="{{ route('sales-person.orders.show', $order->id) }}#add-payment" class="btn btn-sm btn-success">Add Payment</a>
                                    @endif
                                    <a href="{{ route('sales-person.orders.order-form', $order->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-file-earmark-text"></i> Order Form
                                    </a>
                                    <button type="button" class="btn btn-sm text-white js-share-pdf" style="background:#25D366;" data-pdf="{{ route('sales-person.orders.order-form', $order->id) }}" data-filename="{{ $order->orderFormFilename() }}">
                                        <i class="bi bi-whatsapp"></i> Share
                                    </button>
                                    @if($order->status === 'completed')
                                        <a href="{{ route('sales-person.orders.invoice', $order->id) }}" class="btn btn-sm btn-secondary" target="_blank">
                                            <i class="bi bi-receipt"></i> Invoice
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Invoice available after admin completes the order">
                                            <i class="bi bi-receipt"></i> Invoice Pending
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No orders found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@if(session('success') || session('download_order_form_id'))
<script>
    localStorage.removeItem('sp-order-draft-{{ (int) auth()->id() }}');
    localStorage.removeItem('sp-order-draft');
</script>
@endif
@if(session('download_order_form_id'))
<div class="modal fade" id="orderPlacedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="bi bi-check-circle text-success" style="font-size:3rem;"></i>
                <h5 class="mt-3 mb-2">Order placed successfully</h5>
                <p class="mb-1">Close this to share the order form PDF.</p>
                <p class="mb-0 small text-muted">In WhatsApp, choose the saved owner contact. The PDF file is shared. No link.</p>
                @if(session('share_owner_name') || session('share_owner_phone'))
                    <p class="mt-3 mb-0"><strong>Owner:</strong> {{ session('share_owner_name') }} @if(session('share_owner_phone')) · {{ session('share_owner_phone') }} @endif</p>
                @endif
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn text-white" id="closeAndShareOrderForm" style="background:#25D366;" data-pdf="{{ route('sales-person.orders.order-form', session('download_order_form_id')) }}" data-filename="{{ session('share_order_filename') }}">
                    Close and share PDF
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('orderPlacedModal');
        const shareBtn = document.getElementById('closeAndShareOrderForm');
        if (shareBtn && window.rememberOrderFormFile) {
            rememberOrderFormFile(shareBtn, shareBtn.dataset.pdf, shareBtn.dataset.filename);
        }
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
        if (shareBtn) {
            shareBtn.addEventListener('click', function () {
                if (modalEl && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                if (shareBtn._file && window.sharePreparedOrderForm(shareBtn._file)) return;
                window.shareOrderFormPdf(shareBtn.dataset.pdf, shareBtn.dataset.filename);
            });
        }
    });
</script>
@endif
@endsection

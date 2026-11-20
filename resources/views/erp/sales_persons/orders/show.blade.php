@extends('sales_person.layout')

@section('title', 'Order Details')

@php
    $chequeMin = now()->format('Y-m-d');
    $chequeMaxNormal = now()->addDays(45)->format('Y-m-d');
    $chequeMaxException = now()->addDays(180)->format('Y-m-d');
    $chequeMinException = now()->subDays(30)->format('Y-m-d');
@endphp

@section('content')
<div class="row">
    <!-- Order Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order #{{ $order->order_number }}</h5>
                    <a href="{{ route('sales-person.orders.index') }}" class="btn btn-sm btn-secondary">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Business:</strong> {{ filled($order->customer?->business_name) ? $order->customer->business_name : (optional($order->customer)->display_name ?? 'N/A') }} <br>
                        @if($order->customer?->name && filled($order->customer?->business_name) && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
                            <strong>Owner:</strong> {{ $order->customer->name }} <br>
                        @endif
                        <strong>Date:</strong> {{ $order->order_date->format('Y-m-d') }} <br>
                        <strong>Status:</strong> <span class="badge bg-{{ $order->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="col-6 text-end">
                        <a href="{{ route('sales-person.orders.order-form', $order->id) }}" class="btn btn-primary btn-sm">Download Order Form</a>
                        @if((int) $order->user_id === (int) auth()->id() && $order->canSalesPersonEdit())
                            <a href="{{ route('sales-person.orders.edit', $order) }}" class="btn btn-warning btn-sm">Edit</a>
                        @endif
                        <button type="button" class="btn btn-sm text-white js-share-pdf" style="background:#25D366;" data-pdf="{{ route('sales-person.orders.order-form', $order->id) }}" data-filename="{{ $order->orderFormFilename() }}">
                            <i class="bi bi-whatsapp"></i> Share
                        </button>
                        @if($order->canAcceptSaleReturn() && $order->items->contains(fn ($i) => $i->returnableQuantity() > 0))
                            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#saleReturnModal">Sale Return</button>
                        @endif
                    </div>
                </div>

                <h6>Items</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                {{ optional($item->product)->sku ?? '—' }}
                                <small class="d-block text-muted">{{ optional($item->product)->name }}</small>
                                @if($item->variation)
                                    <small class="text-muted">Size: {{ $item->variation->variation_name }}</small>
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if(($order->discount_amount ?? 0) > 0)
                        <tr>
                            <th colspan="3" class="text-end">Subtotal:</th>
                            <th>{{ number_format($order->items->sum('total_price'), 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Discount @if(($order->discount_type ?? '') === 'percentage' && $order->discount_value)({{ number_format($order->discount_value, 0) }}%)@else(Fixed)@endif:</th>
                            <th class="text-danger">- Rs {{ number_format($order->discount_amount, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3" class="text-end">Total Amount:</th>
                            <th>{{ number_format($order->total_amount, 2) }}</th>
                        </tr>
                        @if($order->chargesInvoiceTax())
                        <tr>
                            <th colspan="3" class="text-end">SSCL + VAT:</th>
                            <th>{{ number_format($order->invoice_tax_amount, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Invoice Total (incl. SSCL &amp; VAT):</th>
                            <th>{{ number_format($order->invoice_total, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3" class="text-end">Paid Amount:</th>
                            <th class="text-success">{{ number_format($order->paid_amount, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Due Amount:</th>
                            <th class="text-danger">{{ number_format($order->due_amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h5>Payment History</h5>
            </div>
            <div class="card-body">
                @if($order->payments->isEmpty())
                    <p class="text-muted">No payments recorded.</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment date</th>
                                <th>Method</th>
                                <th>Cheque date</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Cheque / photo</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>
                                    @if(strtolower($payment->payment_method) === 'cheque')
                                        <strong>{{ $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—' }}</strong>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $payment->reference_number ?? '-' }}</td>
                                <td>
                                    {{ number_format($payment->amount, 2) }}
                                    @if($payment->cashNextPaymentDiscountLine())
                                        <div class="small text-success">{{ $payment->cashNextPaymentDiscountLine() }}</div>
                                    @elseif(($payment->discount_amount ?? 0) > 0)
                                        <div class="small text-danger">Disc −{{ number_format($payment->discount_amount, 2) }}
                                            @if($payment->discount_type === 'percentage')
                                                ({{ number_format($payment->discount_value, 2) }}%)
                                            @endif
                                        </div>
                                        <div class="small text-muted">Settled {{ number_format($payment->effectiveSettlementAmount(), 2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->isCheque())
                                        @include('erp.payment-management._cheque-status', ['payment' => $payment, 'readOnly' => true])
                                    @else
                                        <span class="badge bg-success">Paid</span>
                                    @endif
                                </td>
                                <td>
                                    @if(strtolower($payment->payment_method) === 'cheque')
                                        @if($payment->cheque_image_path)
                                            <a href="{{ route('order-payments.cheque-image', $payment) }}" target="_blank" rel="noopener">View photo</a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                        @if($payment->cheque_exception_requested)
                                            <br><span class="badge bg-warning text-dark">Exception</span>
                                        @endif
                                        @if(filled($payment->cheque_exception_reason))
                                            <p class="small text-muted mb-0 mt-1">{{ $payment->cheque_exception_reason }}</p>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $payment->notes ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    @include('erp.orders.partials.add-payment-card', [
        'formAction' => route('sales-person.orders.payment.store', $order->id),
    ])
</div>
</div>
@if($order->due_amount > 0 && $order->canAcceptPayments())
    @include('erp.sales_persons.orders.partials.payment-form-scripts')
@endif

@if($order->canAcceptSaleReturn() && $order->items->contains(fn ($i) => $i->returnableQuantity() > 0))
<div class="modal fade" id="saleReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sale Return — {{ $order->order_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('erp.sale-returns._form', [
                    'order' => $order,
                    'formAction' => route('sales-person.orders.returns.store', $order),
                    'hiddenFields' => ['redirect_sales_person' => 1],
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="sale-return-form-{{ $order->id }}" class="btn btn-warning">Submit Sale Return</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
@section('scripts')
@if(session('clear_edit_draft_id'))
<script>
    localStorage.removeItem('sp-order-edit-{{ (int) session('clear_edit_draft_id') }}');
</script>
@endif
@endsection

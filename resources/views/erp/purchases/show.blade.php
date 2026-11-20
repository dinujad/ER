@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h4 class="mb-0">Purchase {{ $purchase->purchase_no }}</h4>
    <div class="d-flex gap-2">
        @if(auth()->user()?->isSystemAdmin() || auth()->user()?->can_access('accounting.view'))
        <a href="{{ url('accounting/phase6/bills/' . $purchase->id) }}" class="btn btn-outline-dark" title="Lines, VAT, payments, journal entries and history"><i class="bi bi-journal-text"></i> Accounting view</a>
        @endif
        @if($purchase->status === \App\Models\Purchase::STATUS_DRAFT)
        @canRoute('purchases.edit')
<a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-primary">Edit</a>
@endcanRoute
        <form action="{{ route('purchases.confirm', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirm this purchase? Stock will be updated.');">
            @csrf
            <button type="submit" class="btn btn-success">Confirm Purchase</button>
        </form>
        @endif
        @if($purchase->status === \App\Models\Purchase::STATUS_CONFIRMED)
            @php
                $hasReturnable = $purchase->items->contains(fn ($i) => (float) $i->quantity - (float) $i->quantity_returned > 0.0001);
            @endphp
            @if($hasReturnable)
            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#returnToSupplierModal">Return to Supplier</button>
            @endif
        @endif
        @if($purchase->status !== \App\Models\Purchase::STATUS_CANCELLED && $purchase->balance > 0)
        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#addPaymentModal">Add Payment</button>
        @endif
        @if($purchase->status === \App\Models\Purchase::STATUS_CONFIRMED && $purchase->refundable_balance > 0)
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#recordRefundModal">Record Refund</button>
        @endif
        @if($purchase->status !== \App\Models\Purchase::STATUS_CANCELLED)
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel Purchase</button>
        @endif
        @if($purchase->status !== \App\Models\Purchase::STATUS_CANCELLED)
        <a href="{{ route('purchases.report', $purchase) }}" class="btn btn-outline-dark" target="_blank"><i class="bi bi-printer me-1"></i> Report</a>
        @endif
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><strong>Date:</strong> {{ $purchase->date->format('d M Y') }}</div>
                    <div class="col-md-6"><strong>Supplier:</strong> <a href="{{ route('suppliers.show', $purchase->supplier) }}">{{ $purchase->supplier->name }}</a></div>
                    <div class="col-md-6 mt-2"><strong>Type:</strong> {{ $purchase->purchase_type_label }}</div>
                    <div class="col-md-6 mt-2"><strong>Reference:</strong> {{ $purchase->reference_no ?? '—' }}</div>
                    <div class="col-12 mt-2"><strong>Status:</strong>
                        @if($purchase->status === 'draft')<span class="badge bg-secondary">Draft</span>
                        @elseif($purchase->status === 'confirmed')<span class="badge bg-success">Confirmed</span>
                        @else<span class="badge bg-danger">Cancelled</span> @if($purchase->cancel_reason)<br><small class="text-muted">{{ $purchase->cancel_reason }}</small>@endif
                        @endif
                    </div>
                    @if($purchase->notes)<div class="col-12 mt-2"><strong>Notes:</strong> {{ $purchase->notes }}</div>@endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>Items</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Items</th><th>Unit</th><th>Qty</th><th>Returned</th><th>In stock</th><th>Buy Price</th><th>Discount</th><th>Line Total</th></tr></thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        @php $remaining = max(0, (float) $item->quantity - (float) $item->quantity_returned); @endphp
                        <tr>
                            <td>{{ $item->material->name ?? '—' }}</td>
                            <td>{{ $item->unit ?? '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->quantity_returned > 0 ? number_format($item->quantity_returned, 2) : '—' }}</td>
                            <td class="small text-muted">{{ $item->material ? number_format((float) $item->material->quantity, 2) : '—' }}</td>
                            <td>{{ number_format($item->buy_price, 2) }}</td>
                            <td>{{ number_format($item->discount, 2) }}</td>
                            <td>{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><strong>Summary</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>{{ number_format($purchase->subtotal, 2) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span>Discount</span><span>{{ number_format($purchase->discount_amount, 2) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span>Tax</span><span>{{ number_format($purchase->tax_amount, 2) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span>Shipping</span><span>{{ number_format($purchase->shipping_cost, 2) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between mb-2"><strong>Total</strong><strong>{{ number_format($purchase->total, 2) }}</strong></div>
                @if($purchase->total_returned > 0)
                <div class="d-flex justify-content-between mb-2"><span>Returned</span><span class="text-warning">− {{ number_format($purchase->total_returned, 2) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span>Net payable</span><strong>{{ number_format($purchase->net_total, 2) }}</strong></div>
                @endif
                <div class="d-flex justify-content-between mb-2"><span>Total Paid</span><span class="text-success">{{ number_format($purchase->total_paid, 2) }}</span></div>
                @if($purchase->total_refunded > 0)
                <div class="d-flex justify-content-between mb-2"><span>Refunds received</span><span class="text-info">{{ number_format($purchase->total_refunded, 2) }}</span></div>
                @endif
                <div class="d-flex justify-content-between mb-2"><span>Balance</span><span class="{{ $purchase->balance > 0 ? 'text-danger fw-bold' : ($purchase->balance < -0.01 ? 'text-info fw-bold' : '') }}">{{ number_format($purchase->balance, 2) }}</span></div>
                @if($purchase->refundable_balance > 0)
                <p class="small text-info mb-0">Supplier credit (refundable): {{ number_format($purchase->refundable_balance, 2) }}</p>
                @endif
                @if($purchase->total_paid > 0 && $purchase->paid_amount > 0)
                <div class="small text-muted">Initial: @include('erp.purchases._payment-detail', ['method' => $purchase->payment_method, 'reference' => $purchase->payment_reference, 'chequeNumber' => $purchase->cheque_number, 'chequeDate' => $purchase->cheque_date, 'chequeBank' => $purchase->cheque_bank])</div>
                @endif
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><strong>Payment History</strong></div>
            <ul class="list-group list-group-flush">
                @if($purchase->paid_amount > 0)
                <li class="list-group-item d-flex justify-content-between"><span>{{ $purchase->date->format('d M Y') }} — @include('erp.purchases._payment-detail', ['method' => $purchase->payment_method, 'reference' => $purchase->payment_reference, 'chequeNumber' => $purchase->cheque_number, 'chequeDate' => $purchase->cheque_date, 'chequeBank' => $purchase->cheque_bank])</span><strong>{{ number_format($purchase->paid_amount, 2) }}</strong></li>
                @endif
                @forelse($purchase->payments as $pay)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $pay->payment_date->format('d M Y') }} —
                        @if($pay->isRefund())<span class="badge bg-info text-dark me-1">Refund</span>@endif
                        @include('erp.purchases._payment-detail', ['method' => $pay->payment_method, 'reference' => $pay->reference, 'chequeNumber' => $pay->cheque_number, 'chequeDate' => $pay->cheque_date, 'chequeBank' => $pay->cheque_bank])
                    </span>
                    <strong class="{{ $pay->isRefund() ? 'text-info' : '' }}">{{ $pay->isRefund() ? '− ' : '' }}{{ number_format($pay->amount, 2) }}</strong>
                </li>
                @empty
                @if($purchase->paid_amount <= 0)<li class="list-group-item text-muted text-center">No payments yet.</li>@endif
                @endforelse
            </ul>
        </div>
        @if($purchase->returns->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header"><strong>Returns to Supplier</strong></div>
            <ul class="list-group list-group-flush">
                @foreach($purchase->returns as $ret)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span><strong>{{ $ret->return_no }}</strong> · {{ $ret->return_date->format('d M Y') }}</span>
                        <span class="text-warning fw-semibold">{{ number_format($ret->total, 2) }}</span>
                    </div>
                    @if($ret->reason)<div class="small text-muted">{{ $ret->reason }}</div>@endif
                    <ul class="small mb-0 mt-1 ps-3">
                        @foreach($ret->items as $ri)
                        <li>{{ $ri->material->name ?? '—' }}: {{ $ri->quantity }} × {{ number_format($ri->buy_price, 2) }}</li>
                        @endforeach
                    </ul>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @if($purchase->creator)<div class="small text-muted">Created by {{ $purchase->creator->name ?? '—' }} @if($purchase->confirmed_at) · Confirmed {{ $purchase->confirmed_at->format('d M Y H:i') }}@endif</div>@endif
    </div>
</div>

{{-- Add Payment Modal (partial payment: cash, bank transfer, cheque, card) --}}
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('purchase-payments.store') }}">
                @csrf
                <input type="hidden" name="supplier_id" value="{{ $purchase->supplier_id }}">
                <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                <div class="modal-header"><h5 class="modal-title">Add Payment (Partial)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small mb-2"><strong>Due for this purchase only:</strong> <span class="text-danger">{{ number_format($purchase->balance, 2) }}</span></p>
                    <p class="small text-muted mb-2">You can pay up to this amount. Payment will be applied only to this purchase.</p>
                    <div class="mb-2"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $purchase->balance }}" value="{{ $purchase->balance > 0 ? number_format($purchase->balance, 2, '.', '') : '' }}" placeholder="Enter amount (max {{ number_format($purchase->balance, 2) }})" required></div>
                    <div class="mb-2"><label class="form-label">Payment Method <span class="text-danger">*</span></label><select name="payment_method" id="modal_payment_method" class="form-select" required><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="bank_transfer">Bank Transfer</option></select></div>
                    <div class="mb-2"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div id="modal-bank-ref" class="mb-2 d-none"><label class="form-label">Transfer reference</label><input type="text" name="reference" class="form-control" placeholder="Transaction / transfer ref"></div>
                    <div id="modal-cheque-fields" class="mb-2 d-none border rounded p-2 bg-light">
                        <div class="small fw-semibold text-muted mb-2">Cheque details</div>
                        <div class="mb-2"><label class="form-label">Cheque number <span class="text-danger">*</span></label><input type="text" name="cheque_number" class="form-control" placeholder="e.g. 000123"></div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">Cheque date <span class="text-danger">*</span></label><input type="date" name="cheque_date" class="form-control"></div>
                            <div class="col-6"><label class="form-label">Issued bank <span class="text-danger">*</span></label><input type="text" name="cheque_bank" class="form-control" placeholder="Bank name"></div>
                        </div>
                    </div>
                    <div><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Optional"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save Payment</button></div>
            </form>
        </div>
    </div>
</div>

{{-- Return to Supplier --}}
@if($purchase->status === \App\Models\Purchase::STATUS_CONFIRMED)
<div class="modal fade" id="returnToSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('purchases.returns.store', $purchase) }}" onsubmit="return confirm('Return selected items? Stock will be reduced from inventory.');">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Return to Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">Enter return quantity per line. Cannot exceed purchased qty minus already returned. Stock must be available in inventory.</p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Return date <span class="text-danger">*</span></label>
                            <input type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Optional">
                        </div>
                    </div>
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Purchased</th>
                                <th>Already returned</th>
                                <th>In stock</th>
                                <th style="width:120px">Return qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $idx => $item)
                            @php
                                $maxReturn = max(0, round((float) $item->quantity - (float) $item->quantity_returned, 2));
                                $stock = (float) ($item->material->quantity ?? 0);
                            @endphp
                            @if($maxReturn > 0)
                            <tr>
                                <td>{{ $item->material->name ?? '—' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->quantity_returned > 0 ? $item->quantity_returned : '—' }}</td>
                                <td>{{ number_format($stock, 2) }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $idx }}][purchase_item_id]" value="{{ $item->id }}">
                                    <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" step="0.01" min="0" max="{{ min($maxReturn, $stock) }}" placeholder="0" value="{{ old('items.'.$idx.'.quantity') }}">
                                    <span class="small text-muted">max {{ min($maxReturn, $stock) }}</span>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Record Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Record Refund --}}
@if($purchase->status === \App\Models\Purchase::STATUS_CONFIRMED && $purchase->refundable_balance > 0)
<div class="modal fade" id="recordRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('purchase-payments.refund') }}">
                @csrf
                <input type="hidden" name="supplier_id" value="{{ $purchase->supplier_id }}">
                <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                <div class="modal-header"><h5 class="modal-title">Record Refund from Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small mb-2"><strong>Refundable (after returns):</strong> {{ number_format($purchase->refundable_balance, 2) }}</p>
                    <div class="mb-2">
                        <label class="form-label">Link to return (optional)</label>
                        <select name="purchase_return_id" class="form-select">
                            <option value="">— General refund —</option>
                            @foreach($purchase->returns as $ret)
                            <option value="{{ $ret->id }}">{{ $ret->return_no }} ({{ number_format($ret->total, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $purchase->refundable_balance }}" value="{{ number_format($purchase->refundable_balance, 2, '.', '') }}" required></div>
                    <div class="mb-2"><label class="form-label">Method <span class="text-danger">*</span></label><select name="payment_method" id="refund_payment_method" class="form-select" required><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="bank_transfer">Bank Transfer</option></select></div>
                    <div class="mb-2"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div id="refund-bank-ref" class="mb-2 d-none"><label class="form-label">Transfer reference</label><input type="text" name="reference" class="form-control"></div>
                    <div id="refund-cheque-fields" class="mb-2 d-none border rounded p-2 bg-light">
                        <div class="mb-2"><label class="form-label">Cheque number</label><input type="text" name="cheque_number" class="form-control"></div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label">Cheque date</label><input type="date" name="cheque_date" class="form-control"></div>
                            <div class="col-6"><label class="form-label">Bank</label><input type="text" name="cheque_bank" class="form-control"></div>
                        </div>
                    </div>
                    <div><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-info">Save Refund</button></div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" onsubmit="return confirm('Cancel this purchase? This cannot be undone.');">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Cancel Purchase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="cancel_reason" class="form-control" rows="3" required placeholder="Enter reason for cancellation"></textarea>
                    @if($purchase->status === 'confirmed')<p class="small text-warning mt-2">Stock will be reversed for confirmed purchase.</p>@endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-danger">Cancel Purchase</button></div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var sel = document.getElementById('modal_payment_method');
    if (sel) {
        function toggleModalPaymentFields() {
            var m = sel.value;
            var bank = document.getElementById('modal-bank-ref');
            var cheque = document.getElementById('modal-cheque-fields');
            if (bank) bank.classList.toggle('d-none', m !== 'bank_transfer');
            if (cheque) {
                cheque.classList.toggle('d-none', m !== 'cheque');
                cheque.querySelectorAll('input').forEach(function(inp) {
                    inp.required = m === 'cheque';
                });
            }
        }
        sel.addEventListener('change', toggleModalPaymentFields);
        toggleModalPaymentFields();
    }
    var refundSel = document.getElementById('refund_payment_method');
    if (refundSel) {
        function toggleRefundFields() {
            var m = refundSel.value;
            var bank = document.getElementById('refund-bank-ref');
            var cheque = document.getElementById('refund-cheque-fields');
            if (bank) bank.classList.toggle('d-none', m !== 'bank_transfer');
            if (cheque) {
                cheque.classList.toggle('d-none', m !== 'cheque');
                cheque.querySelectorAll('input').forEach(function(inp) { inp.required = m === 'cheque'; });
            }
        }
        refundSel.addEventListener('change', toggleRefundFields);
        toggleRefundFields();
    }
    function openModalFromHash(hash, modalId) {
        if (window.location.hash !== hash) return;
        var modalEl = document.getElementById(modalId);
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }
    openModalFromHash('#addPaymentModal', 'addPaymentModal');
    openModalFromHash('#returnToSupplierModal', 'returnToSupplierModal');
    openModalFromHash('#recordRefundModal', 'recordRefundModal');
})();
</script>
@endsection

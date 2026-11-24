@extends('accounting::phase2._page')

@php
    $status = $order->invoiceStatus();
    $pageTitle = ($order->invoice_number ? 'Invoice ' . $order->invoice_number : 'Order ' . $order->order_number);
    $pageSub = ($order->customer?->business_name ?: $order->customer?->name ?: 'No customer') . ' · ' . $order->invoiceStatusLabel();
    $dueDate = $order->invoiceDueDate();
    $returnsBilled = $order->total_returned_billed;
    $creditNotesTotal = round((float) $creditNotes->where('type', 'adjustment')->where('status', '!=', 'void')->sum('total'), 2);
    $canManage = auth()->user()?->isSystemAdmin() || auth()->user()?->can_access('accounting.manage');
@endphp

@section('page_actions')
    @if($order->isInvoiceIssued() && \Illuminate\Support\Facades\Route::has('orders.invoice'))
        <a href="{{ route('orders.invoice', $order->id) }}" class="btn btn-primary btn-sm"><i class="bi bi-filetype-pdf"></i> Invoice PDF</a>
    @endif
    @if(\Illuminate\Support\Facades\Route::has('orders.show'))
        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cart"></i> ERP order</a>
    @endif
    @if($canManage && $order->isInvoiceIssued() && $status !== 'voided')
        <a href="{{ url('accounting/phase6/credit-notes/create?order_id=' . $order->id) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-file-earmark-minus"></i> Credit note</a>
        @if((float) $order->due_amount > 0.009)
            <form method="POST" action="{{ url('accounting/phase6/invoices/' . $order->id . '/reminder') }}" class="d-inline">@csrf
                <button class="btn btn-outline-info btn-sm" title="Send an SMS payment reminder to the customer"><i class="bi bi-bell"></i> Send reminder</button>
            </form>
        @endif
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#voidBox"><i class="bi bi-x-octagon"></i> Void</button>
    @endif
@endsection

@section('page_body')

@if($canManage && $order->isInvoiceIssued() && $status !== 'voided')
<div class="collapse mb-3" id="voidBox">
    <div class="card border-danger"><div class="card-body">
        <form method="POST" action="{{ url('accounting/phase6/invoices/' . $order->id . '/void') }}" class="row g-2 align-items-end"
              onsubmit="return confirm('Void this invoice? Its journal entries are reversed and the issued stock is returned. The invoice number is kept and cannot be reused.');">
            @csrf
            <div class="col-md-8">
                <label class="form-label small mb-0">Reason for voiding (required — shown in history)</label>
                <input name="reason" class="form-control form-control-sm" required maxlength="255">
            </div>
            <div class="col-auto"><button class="btn btn-danger btn-sm">Void invoice</button></div>
            <div class="col-12 small text-muted">
                Only invoices with no payments, returns or credit notes can be voided. Otherwise raise a credit note instead (Xero rule).
            </div>
        </form>
    </div></div>
</div>
@endif

<nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Invoice sections">
    <a href="#lines" class="btn btn-sm btn-outline-secondary">Lines</a>
    <a href="#source" class="btn btn-sm btn-outline-secondary">Source</a>
    <a href="#payments" class="btn btn-sm btn-outline-secondary">Payments</a>
    <a href="#returns" class="btn btn-sm btn-outline-secondary">Returns &amp; credit notes</a>
    <a href="#journal" class="btn btn-sm btn-outline-secondary">Journal entries</a>
    <a href="#history" class="btn btn-sm btn-outline-secondary">History</a>
</nav>

{{-- Header --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="h5 mb-1">{{ $order->invoice_number ?: 'Not yet issued' }}
                        <span class="badge {{ $order->invoiceStatusBadge() }} align-middle">{{ $order->invoiceStatusLabel() }}</span>
                        @if($order->isInvoiceIssued())
                            <span class="badge bg-light text-dark border align-middle">{{ filled($order->customer?->tin) ? 'TAX INVOICE' : 'INVOICE' }}</span>
                        @endif
                    </div>
                    <div class="small text-muted">Order {{ $order->order_number }}</div>
                </div>
                <div class="small text-end">
                    <div>Invoice date: <strong>{{ $order->invoiceDate()?->format('Y-m-d') ?? '—' }}</strong></div>
                    <div>Due date: <strong class="{{ $status === 'overdue' ? 'text-danger' : '' }}">{{ $order->isInvoiceIssued() ? ($dueDate?->format('Y-m-d') ?? '—') : '—' }}</strong>
                        @if($order->isInvoiceIssued()) <span class="text-muted">({{ $order->paymentTermsDays() }} days)</span>@endif</div>
                    @if($status === 'overdue')<div class="text-danger fw-semibold">{{ $order->daysOverdue() }} days overdue</div>@endif
                    @if($order->last_reminder_at)<div class="text-muted">Last reminder: {{ $order->last_reminder_at->format('Y-m-d H:i') }}</div>@endif
                </div>
            </div>
            <hr>
            <div class="row small">
                <div class="col-md-6">
                    <div class="text-muted">Customer</div>
                    <div class="fw-semibold">
                        @if($order->customer && \Illuminate\Support\Facades\Route::has('erp.customers.show'))
                            <a href="{{ route('erp.customers.show', $order->customer->id) }}">{{ $order->customer->business_name ?: $order->customer->name }}</a>
                        @else
                            {{ $order->customer?->business_name ?: ($order->customer?->name ?? '—') }}
                        @endif
                    </div>
                    <div>TIN: {{ $order->customer?->tin ?: '— (not VAT registered)' }}</div>
                    <div>{{ $order->customer?->phone }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted">Sales person</div>
                    <div class="fw-semibold">{{ $order->salesPerson?->name ?? '—' }}</div>
                    <div>Channel: {{ ucfirst($order->stockChannel()) }}</div>
                    @if($order->invoice_voided_at)
                        <div class="text-danger mt-1">Voided {{ $order->invoice_voided_at->format('Y-m-d H:i') }} — {{ $order->invoice_void_reason }}</div>
                    @endif
                </div>
            </div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body small">
            <div class="d-flex justify-content-between"><span>Invoice total</span><strong>{{ number_format($order->invoice_total, 2) }}</strong></div>
            @if($returnsBilled > 0)<div class="d-flex justify-content-between text-warning"><span>Less returns (incl. tax)</span><span>−{{ number_format($returnsBilled, 2) }}</span></div>@endif
            @if($creditNotesTotal > 0)<div class="d-flex justify-content-between text-warning"><span>Less credit notes</span><span>−{{ number_format($creditNotesTotal, 2) }}</span></div>@endif
            <div class="d-flex justify-content-between text-success"><span>Paid</span><span>−{{ number_format((float) $order->paid_amount, 2) }}</span></div>
            @if($order->total_refunded > 0)<div class="d-flex justify-content-between"><span>Refunded to customer</span><span>+{{ number_format($order->total_refunded, 2) }}</span></div>@endif
            @if((float) $order->claim_credit_amount > 0)<div class="d-flex justify-content-between text-info"><span>Return claim applied</span><span>−{{ number_format((float) $order->claim_credit_amount, 2) }}</span></div>@endif
            <hr class="my-2">
            <div class="d-flex justify-content-between fs-5"><span>Balance due</span><strong class="{{ $order->due_amount > 0 ? 'text-danger' : 'text-success' }}">{{ $status === 'voided' ? '0.00' : number_format($order->due_amount, 2) }}</strong></div>
            @if($arCheck['account_found'] && abs($arCheck['difference']) > 0.009 && $status !== 'draft')
                <div class="alert alert-warning py-1 px-2 mt-2 mb-0">
                    Ledger check: Accounts Receivable from this invoice's journals is Rs {{ number_format($arCheck['ledger'], 2) }},
                    balance due is Rs {{ number_format($arCheck['invoice'], 2) }} (difference {{ number_format($arCheck['difference'], 2) }}).
                    Usual causes: a return-claim credit or a payment-line discount that is not posted to the ledger, a cheque not yet cleared, or a journal still pending.
                </div>
            @elseif($arCheck['account_found'] && $status !== 'draft')
                <div class="text-success mt-2"><i class="bi bi-check-circle"></i> Ledger agrees with the balance due.</div>
            @endif
        </div></div>
    </div>
</div>

{{-- Lines --}}
<div class="card mb-3" id="lines"><div class="card-header"><strong>Lines</strong></div><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle">
    <thead class="table-light"><tr><th>Item</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end">Returned</th><th class="text-end">Unit price</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product?->name ?? 'Product' }}@if($item->variation?->variation_name) <span class="text-muted">({{ $item->variation->variation_name }})</span>@endif</td>
            <td class="small text-muted">{{ $item->product?->sku ?? '—' }}</td>
            <td class="text-end">{{ number_format((float) $item->quantity, 2) }}</td>
            <td class="text-end text-warning">{{ (float) $item->quantity_returned > 0 ? number_format((float) $item->quantity_returned, 2) : '' }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $item->unit_price, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $item->total_price, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot class="small">
        <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end font-monospace">{{ number_format((float) ($order->subtotal ?? $order->items->sum('total_price')), 2) }}</td></tr>
        @if((float) $order->discount_amount > 0)<tr><td colspan="5" class="text-end">Less discount</td><td class="text-end font-monospace">({{ number_format((float) $order->discount_amount, 2) }})</td></tr>@endif
        @if((float) $order->shipping > 0)<tr><td colspan="5" class="text-end">Transport</td><td class="text-end font-monospace">{{ number_format((float) $order->shipping, 2) }}</td></tr>@endif
        @if((float) $order->invoice_other_expenses > 0)<tr><td colspan="5" class="text-end">Other expenses</td><td class="text-end font-monospace">{{ number_format((float) $order->invoice_other_expenses, 2) }}</td></tr>@endif
        @if($order->invoice_tax_included > 0)
            <tr><td colspan="5" class="text-end">Item total</td><td class="text-end font-monospace">{{ number_format((float) $order->total_amount, 2) }}</td></tr>
            <tr><td colspan="5" class="text-end text-muted">Less: {{ $order->invoice_sscl_inclusive && $order->invoice_vat_inclusive ? 'SSCL & VAT' : ($order->invoice_vat_inclusive ? 'VAT' : 'SSCL') }} already included in item prices</td><td class="text-end font-monospace">({{ number_format($order->invoice_tax_included, 2) }})</td></tr>
        @endif
        <tr class="fw-semibold"><td colspan="5" class="text-end">Goods value (excl. tax) — posted to Sales</td><td class="text-end font-monospace">{{ number_format($order->goodsValue(), 2) }}</td></tr>
        @if($order->chargesInvoiceTax())
            <tr><td colspan="5" class="text-end">SSCL {{ number_format($tax['sscl_rate'], 1) }}% (on goods){{ $order->invoice_sscl_inclusive ? ' · included in prices' : '' }}</td><td class="text-end font-monospace">{{ number_format($tax['sscl'], 2) }}</td></tr>
            <tr><td colspan="5" class="text-end">VAT {{ number_format($tax['rate'], 1) }}% (on goods + SSCL)@if(isset($order->invoice_vat_code)) · {{ \App\Support\VatTreatment::label($order->invoice_vat_code) }}@endif{{ $order->invoice_vat_inclusive ? ' · included in prices' : '' }}</td><td class="text-end font-monospace">{{ number_format($tax['vat'], 2) }}</td></tr>
        @elseif($order->isInvoiceIssued())
            <tr><td colspan="6" class="text-end text-muted">No SSCL / VAT charged in the system for this invoice (issued before tax was charged on top).</td></tr>
        @endif
        <tr class="fw-bold"><td colspan="5" class="text-end">Invoice total</td><td class="text-end font-monospace">{{ number_format($order->invoice_total, 2) }}</td></tr>
    </tfoot>
</table>
</div>
</div></div>

{{-- Source chain --}}
<div class="card mb-3" id="source"><div class="card-header"><strong>Where it came from</strong> <span class="small text-muted">— every step that built this invoice</span></div><div class="card-body">
    @foreach($timeline as $e)
        <div class="d-flex gap-3 mb-2">
            <div class="text-primary fs-5"><i class="bi {{ $e['icon'] }}"></i></div>
            <div>
                <div>
                    @if($e['url'])<a href="{{ $e['url'] }}" class="fw-semibold">{{ $e['title'] }}</a>@else<span class="fw-semibold">{{ $e['title'] }}</span>@endif
                    <span class="small text-muted ms-2">{{ $e['when'] ? \Illuminate\Support\Carbon::parse($e['when'])->format('Y-m-d H:i') : '' }}</span>
                </div>
                <div class="small text-muted">{{ $e['detail'] }}</div>
            </div>
        </div>
    @endforeach
    @if($stockMoves->isNotEmpty())
        <details class="small mt-2"><summary class="text-primary" style="cursor:pointer">Stock movements ({{ $stockMoves->count() }})</summary>
            <table class="table table-sm mt-2 mb-0"><thead><tr><th>When</th><th>Type</th><th>Channel</th><th>Product</th><th class="text-end">Qty</th><th>Note</th></tr></thead><tbody>
            @foreach($stockMoves as $m)
                <tr><td>{{ $m->created_at }}</td><td>{{ $m->transaction_type }}</td><td>{{ $m->channel }}</td><td>#{{ $m->product_id }}{{ $m->product_variation_id ? ' / var ' . $m->product_variation_id : '' }}</td><td class="text-end">{{ number_format((float) $m->quantity, 2) }}</td><td>{{ $m->notes }}</td></tr>
            @endforeach
            </tbody></table>
        </details>
    @endif
</div></div>

{{-- Payments --}}
<div class="card mb-3" id="payments"><div class="card-header"><strong>Payments &amp; refunds</strong></div><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle">
    <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Method</th><th>Reference</th><th>Cheque</th><th class="text-end">Amount</th><th class="text-end">Settled</th><th>Recorded by</th></tr></thead>
    <tbody>
    @forelse($payments as $p)
        <tr class="{{ $p->countsTowardPaid() || $p->isRefund() ? '' : 'text-muted text-decoration-line-through' }}">
            <td class="text-nowrap">{{ $p->payment_date?->format('Y-m-d') }}</td>
            <td>{{ $p->isRefund() ? 'Refund' : 'Payment' }}</td>
            <td>{{ ucfirst((string) $p->payment_method) }}</td>
            <td class="small">{{ $p->reference_number }}</td>
            <td class="small">@if($p->isCheque())<span class="badge {{ $p->chequeStatusBadgeClass() }}">{{ $p->chequeStatusLabel() }}</span> {{ $p->cheque_number }}@endif</td>
            <td class="text-end font-monospace">{{ number_format((float) $p->amount, 2) }}</td>
            <td class="text-end font-monospace">{{ $p->isRefund() ? '' : number_format($p->effectiveSettlementAmount(), 2) }}</td>
            <td class="small">{{ $recordedBy[\App\Models\Payment::class . '#' . $p->id] ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No payments yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div></div>

{{-- Returns & credit notes --}}
<div class="card mb-3" id="returns"><div class="card-header"><strong>Returns &amp; credit notes</strong></div><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle" id="credit-notes">
    <thead class="table-light"><tr><th>Date</th><th>Document</th><th>Reason</th><th class="text-end">Goods</th><th class="text-end">SSCL + VAT</th><th class="text-end">Total credited</th><th></th></tr></thead>
    <tbody>
    @foreach($returns as $r)
        @php $billed = $order->withInvoiceTax((float) $r->total); @endphp
        <tr>
            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($r->return_date ?? $r->created_at)->format('Y-m-d') }}</td>
            <td>Sale return {{ $r->return_no }} <span class="text-muted small">({{ $r->items->count() }} line(s))</span></td>
            <td class="small">{{ $r->reason }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $r->total, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($billed - (float) $r->total, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($billed, 2) }}</td>
            <td class="small">@php $cn = $creditNotes->firstWhere('sale_return_id', $r->id); @endphp
                @if($cn)<a href="{{ url('accounting/phase6/credit-notes/' . $cn->id . '/pdf') }}" class="btn btn-outline-secondary btn-xs"><i class="bi bi-filetype-pdf"></i> {{ $cn->credit_note_number }}</a>@endif</td>
        </tr>
    @endforeach
    @foreach($creditNotes->where('type', 'adjustment') as $cn)
        <tr class="{{ $cn->status === 'void' ? 'text-muted text-decoration-line-through' : '' }}">
            <td class="text-nowrap">{{ $cn->credit_note_date?->format('Y-m-d') }}</td>
            <td>Credit note {{ $cn->credit_note_number }}</td>
            <td class="small">{{ $cn->reason }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $cn->goods_amount, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $cn->sscl_amount + (float) $cn->vat_amount, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $cn->total, 2) }}</td>
            <td><a href="{{ url('accounting/phase6/credit-notes/' . $cn->id . '/pdf') }}" class="btn btn-outline-secondary btn-xs"><i class="bi bi-filetype-pdf"></i> PDF</a></td>
        </tr>
    @endforeach
    @if($returns->isEmpty() && $creditNotes->where('type', 'adjustment')->isEmpty())
        <tr><td colspan="7" class="text-muted">No returns or credit notes.</td></tr>
    @endif
    </tbody>
</table>
</div>
</div></div>

{{-- Journal --}}
<div class="card mb-3" id="journal"><div class="card-header"><strong>Journal entries</strong> <span class="small text-muted">— the double entry this invoice and its payments / returns posted</span></div><div class="card-body">
    @include('accounting::phase6.partials.journal_list', ['journal' => $journal])
</div></div>

{{-- History --}}
<div class="card mb-3" id="history"><div class="card-header d-flex justify-content-between"><strong>History</strong>
    <span class="small text-muted">Invoice, lines, payments, returns and credit notes</span></div><div class="card-body">
    @include('accounting::phase6.partials.history', ['logs' => $history])
</div></div>
@endsection

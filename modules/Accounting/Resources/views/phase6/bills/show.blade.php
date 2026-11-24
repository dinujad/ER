@extends('accounting::phase2._page')

@php
    $st = $purchase->billStatus();
    $pageTitle = 'Bill ' . $purchase->purchase_no;
    $pageSub = ($purchase->supplier?->company_name ?: $purchase->supplier?->name) . ' · ' . $purchase->billStatusLabel();
    $canManage = auth()->user()?->isSystemAdmin() || auth()->user()?->can_access('accounting.manage');
@endphp

@section('page_actions')
    @if(\Illuminate\Support\Facades\Route::has('purchases.show'))
        <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bag"></i> ERP purchase</a>
    @endif
    @if(\Illuminate\Support\Facades\Route::has('purchases.report'))
        <a href="{{ route('purchases.report', $purchase->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-pdf"></i> Purchase report</a>
    @endif
    @if($canManage && $purchase->status === 'confirmed' && \App\Models\Purchase::hasPayablesColumns() && !$purchase->approved_at)
        <form method="POST" action="{{ url('accounting/phase6/bills/' . $purchase->id . '/approve') }}" class="d-inline">@csrf
            <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Approve for payment</button>
        </form>
    @endif
@endsection

@section('page_body')

<nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Bill sections">
    <a href="#lines" class="btn btn-sm btn-outline-secondary">Lines</a>
    <a href="#payments" class="btn btn-sm btn-outline-secondary">Payments</a>
    <a href="#returns" class="btn btn-sm btn-outline-secondary">Returns</a>
    <a href="#journal" class="btn btn-sm btn-outline-secondary">Journal entries</a>
    <a href="#history" class="btn btn-sm btn-outline-secondary">History</a>
</nav>

<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <div class="h5 mb-1">{{ $purchase->purchase_no }} <span class="badge {{ $purchase->billStatusBadge() }} align-middle">{{ $purchase->billStatusLabel() }}</span></div>
                <div class="small text-muted">Supplier reference: {{ $purchase->reference_no ?: '—' }} · {{ $purchase->purchase_type_label }}</div>
            </div>
            <div class="small text-end">
                <div>Bill date: <strong>{{ $purchase->date?->format('Y-m-d') }}</strong></div>
                @if($purchase->status === 'confirmed')
                    <div>Due date: <strong class="{{ $st === 'overdue' ? 'text-danger' : '' }}">{{ $purchase->dueDate()?->format('Y-m-d') }}</strong> <span class="text-muted">({{ $purchase->supplierTermsDays() }} days)</span></div>
                    @if($st === 'overdue')<div class="text-danger fw-semibold">{{ $purchase->daysOverdue() }} days overdue</div>@endif
                @endif
            </div>
        </div>
        <hr>
        <div class="row small">
            <div class="col-md-6">
                <div class="text-muted">Supplier</div>
                <div class="fw-semibold">
                    @if($purchase->supplier && \Illuminate\Support\Facades\Route::has('suppliers.show'))
                        <a href="{{ route('suppliers.show', $purchase->supplier->id) }}">{{ $purchase->supplier->company_name ?: $purchase->supplier->name }}</a>
                    @else
                        {{ $purchase->supplier?->company_name ?: $purchase->supplier?->name }}
                    @endif
                </div>
                <div>VAT no.: {{ $purchase->supplier?->tax_vat_no ?: '— (not VAT registered)' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted">Where it came from</div>
                <div>Entered by {{ $purchase->creator?->name ?? '—' }} on {{ $purchase->created_at?->format('Y-m-d H:i') }}</div>
                <div>Confirmed (stock received): {{ $purchase->confirmed_at?->format('Y-m-d H:i') ?? '—' }}</div>
                @if(\App\Models\Purchase::hasPayablesColumns())
                    <div>Approved for payment: {{ $purchase->approved_at?->format('Y-m-d H:i') ?? 'not yet' }}</div>
                @endif
                @if($purchase->cancelled_at)<div class="text-danger">Cancelled {{ $purchase->cancelled_at->format('Y-m-d') }} — {{ $purchase->cancel_reason }}</div>@endif
            </div>
        </div>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body small">
        <div class="d-flex justify-content-between"><span>Bill total</span><strong>{{ number_format((float) $purchase->total, 2) }}</strong></div>
        @if($purchase->total_returned_billed > 0)<div class="d-flex justify-content-between text-warning"><span>Less returns</span><span>−{{ number_format($purchase->total_returned_billed, 2) }}</span></div>@endif
        <div class="d-flex justify-content-between text-success"><span>Paid</span><span>−{{ number_format((float) $purchase->total_paid, 2) }}</span></div>
        @if($purchase->total_refunded > 0)<div class="d-flex justify-content-between"><span>Refunds received</span><span>+{{ number_format($purchase->total_refunded, 2) }}</span></div>@endif
        <hr class="my-2">
        <div class="d-flex justify-content-between fs-5"><span>Balance to pay</span><strong class="{{ (float) $purchase->balance > 0.009 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $purchase->balance, 2) }}</strong></div>
        @if($apCheck['account_found'] && $purchase->status === 'confirmed')
            @if(abs($apCheck['difference']) > 0.009)
                <div class="alert alert-warning py-1 px-2 mt-2 mb-0">Ledger check: Accounts Payable from this bill's journals is Rs {{ number_format($apCheck['ledger'], 2) }}, balance is Rs {{ number_format($apCheck['bill'], 2) }} (difference {{ number_format($apCheck['difference'], 2) }}). Usual causes: a cheque not yet cleared, or a journal still pending.</div>
            @else
                <div class="text-success mt-2"><i class="bi bi-check-circle"></i> Ledger agrees with the balance.</div>
            @endif
        @endif
    </div></div></div>
</div>

<div class="card mb-3" id="lines"><div class="card-header"><strong>Lines</strong></div><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle">
    <thead class="table-light"><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Returned</th><th class="text-end">Buy price</th><th class="text-end">Discount</th><th class="text-end">Line tax</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    @foreach($purchase->items as $item)
        <tr>
            <td>{{ $item->material?->name ?? 'Item' }} @if($item->is_fixed_asset)<span class="badge bg-secondary">Fixed asset</span>@endif</td>
            <td class="text-end">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td>
            <td class="text-end text-warning">{{ (float) $item->quantity_returned > 0 ? number_format((float) $item->quantity_returned, 2) : '' }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $item->buy_price, 2) }}</td>
            <td class="text-end font-monospace">{{ (float) $item->discount > 0 ? number_format((float) $item->discount, 2) : '' }}</td>
            <td class="text-end font-monospace">{{ (float) $item->tax_amount > 0 ? number_format((float) $item->tax_amount, 2) : '' }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $item->line_total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot class="small">
        <tr><td colspan="6" class="text-end">Subtotal</td><td class="text-end font-monospace">{{ number_format((float) $purchase->subtotal, 2) }}</td></tr>
        @if((float) $purchase->discount_amount > 0)<tr><td colspan="6" class="text-end">Less discount</td><td class="text-end font-monospace">({{ number_format((float) $purchase->discount_amount, 2) }})</td></tr>@endif
        @if((float) $purchase->tax_amount > 0)<tr><td colspan="6" class="text-end">Tax</td><td class="text-end font-monospace">{{ number_format((float) $purchase->tax_amount, 2) }}</td></tr>@endif
        @if((float) $purchase->shipping_cost > 0)<tr><td colspan="6" class="text-end">Shipping / other</td><td class="text-end font-monospace">{{ number_format((float) $purchase->shipping_cost, 2) }}</td></tr>@endif
        <tr class="fw-bold"><td colspan="6" class="text-end">Bill total</td><td class="text-end font-monospace">{{ number_format((float) $purchase->total, 2) }}</td></tr>
        <tr><td colspan="7" class="text-end {{ $inputVat > 0 ? 'text-success' : 'text-muted' }}">
            @if($inputVat > 0)
                Input VAT claimed: Rs {{ number_format($inputVat, 2) }} → VAT Input (1165), included in the VAT return.
            @elseif((float) $purchase->tax_amount > 0)
                Tax not claimed as input VAT — included in stock cost.
            @else
                No tax on this bill.
            @endif
        </td></tr>
    </tfoot>
</table>
</div>
</div></div>

<div class="card mb-3" id="payments"><div class="card-header"><strong>Payments &amp; refunds</strong></div><div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    @forelse($payments as $p)
        <tr>
            <td>{{ $p->payment_date?->format('Y-m-d') }}</td>
            <td>{{ ucfirst((string) ($p->entry_type ?: 'payment')) }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', (string) $p->payment_method)) }}</td>
            <td class="small">{{ $p->reference }} {{ $p->cheque_number ? '· cheque ' . $p->cheque_number : '' }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $p->amount, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No payments yet.@if((float) $purchase->paid_amount > 0) Header payment: Rs {{ number_format((float) $purchase->paid_amount, 2) }} ({{ $purchase->payment_method }}).@endif</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>

<div class="card mb-3" id="returns"><div class="card-header"><strong>Returns to supplier</strong></div><div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Return</th><th>Reason</th><th class="text-end">Goods</th><th class="text-end">Input VAT reversed</th></tr></thead>
    <tbody>
    @forelse($returns as $r)
        <tr>
            <td>{{ $r->return_date?->format('Y-m-d') }}</td>
            <td>{{ $r->return_no }} <span class="small text-muted">({{ $r->items->count() }} line(s))</span></td>
            <td class="small">{{ $r->reason }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $r->total, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($purchase->returnedInputVat((float) $r->total), 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No returns.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>

<div class="card mb-3" id="journal"><div class="card-header"><strong>Journal entries</strong></div><div class="card-body">
    @include('accounting::phase6.partials.journal_list', ['journal' => $journal])
</div></div>

<div class="card mb-3" id="history"><div class="card-header"><strong>History</strong></div><div class="card-body">
    @include('accounting::phase6.partials.history', ['logs' => $history])
</div></div>
@endsection

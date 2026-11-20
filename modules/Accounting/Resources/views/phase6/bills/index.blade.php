@extends('accounting::phase2._page')

@php $pageTitle = 'Bills'; $pageSub = 'Supplier purchases as bills — click any bill for lines, VAT, payments, journal entries and history'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/bills/payment-run') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-cash-stack"></i> Payment run</a>
    @if(\Illuminate\Support\Facades\Route::has('purchases.create'))
        <a href="{{ route('purchases.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus"></i> New purchase</a>
    @endif
@endsection

@section('page_body')
@php
    $tabs = ['all' => 'All', 'draft' => 'Draft', 'unpaid' => 'Awaiting payment', 'overdue' => 'Overdue', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];
    if (config('dermas.require_bill_approval')) { $tabs = array_merge(['all' => 'All', 'awaiting_approval' => 'Awaiting approval'], $tabs); }
@endphp
<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}">{{ $label }}</a></li>
    @endforeach
</ul>

<form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="status" value="{{ $status }}">
    <div class="col-md-3">
        <label class="form-label small mb-0">Supplier</label>
        <select name="supplier_id" class="form-select form-select-sm">
            <option value="">All suppliers</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected(($f['supplier_id'] ?? null) == $s->id)>{{ $s->company_name ?: $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label small mb-0">Search</label><input name="q" value="{{ $f['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Bill no. / supplier ref."></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="small text-muted mb-2">{{ number_format($summary['count']) }} bill(s) · total Rs {{ number_format($summary['total'], 2) }}@if($summary['balance'] !== null) · to pay Rs {{ number_format($summary['balance'], 2) }}@endif</div>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr><th>Bill</th><th>Date</th><th>Due</th><th>Supplier</th><th>Supplier ref.</th><th class="text-end">VAT</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($bills as $b)
        @php $st = $b->billStatus(); @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ url('accounting/phase6/bills/' . $b->id) }}'">
            <td><a href="{{ url('accounting/phase6/bills/' . $b->id) }}" class="fw-semibold">{{ $b->purchase_no }}</a></td>
            <td class="small text-nowrap">{{ $b->date?->format('Y-m-d') }}</td>
            <td class="small text-nowrap {{ $st === 'overdue' ? 'text-danger fw-semibold' : '' }}">{{ $b->status === 'confirmed' ? $b->dueDate()?->format('Y-m-d') : '—' }}</td>
            <td class="small">{{ $b->supplier?->company_name ?: $b->supplier?->name }}</td>
            <td class="small">{{ $b->reference_no }}</td>
            <td class="text-end font-monospace small">{{ (float) $b->tax_amount > 0 ? number_format((float) $b->tax_amount, 2) : '' }}@if($b->input_vat_claimable && (float) $b->tax_amount > 0) <span class="badge bg-success-subtle text-success border">claim</span>@endif</td>
            <td class="text-end font-monospace">{{ number_format((float) $b->total, 2) }}</td>
            <td class="text-end font-monospace {{ (float) $b->balance > 0.009 && $b->status === 'confirmed' ? 'text-danger' : 'text-muted' }}">{{ $b->status === 'confirmed' ? number_format((float) $b->balance, 2) : '—' }}</td>
            <td><span class="badge {{ $b->billStatusBadge() }}">{{ $b->billStatusLabel() }}</span></td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-muted">No bills match these filters.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $bills->links() }}
@endsection

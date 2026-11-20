@extends('accounting::phase2._page')

@php $pageTitle = 'Tax Invoice Register'; $pageSub = $from . ' to ' . $to . ' · every invoice number issued, with numbering check'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/vat-return?from=' . $from . '&to=' . $to) }}" class="btn btn-outline-secondary btn-sm">VAT return</a>
@endsection

@section('page_body')
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-2"><label class="form-label small mb-0">From</label><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-0">To</label><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Run</button></div>
</form>

@if(count($register['gaps']) > 0)
    <div class="alert alert-warning py-2">
        <strong>{{ count($register['gaps']) }} missing serial number(s)</strong> in this period:
        {{ implode(', ', array_slice($register['gaps'], 0, 40)) }}{{ count($register['gaps']) > 40 ? ' …' : '' }}.
        IRD expects tax invoice serials without gaps — check for deleted orders, or invoices issued in another period.
    </div>
@else
    <div class="alert alert-success py-2">No gaps in the invoice serial numbers for this period.</div>
@endif
<div class="small text-muted mb-2">{{ $register['rows']->count() }} invoice(s) · {{ $register['rows']->where('type', 'Tax invoice')->count() }} tax invoices (customer has TIN) · {{ $register['missing_tin'] }} normal invoices (no TIN)</div>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr><th>Serial</th><th>Invoice no.</th><th>Date</th><th>Type</th><th>Customer</th><th>TIN</th><th class="text-end">Total</th><th></th></tr></thead>
    <tbody>
    @forelse($register['rows'] as $r)
        <tr class="{{ $r->voided ? 'text-muted text-decoration-line-through' : '' }}">
            <td class="font-monospace">{{ $r->serial }}</td>
            <td><a href="{{ url('accounting/phase6/invoices/' . $r->id) }}">{{ $r->number }}</a></td>
            <td class="text-nowrap">{{ $r->date }}</td>
            <td>{{ $r->type }}</td>
            <td>{{ $r->customer }}</td>
            <td class="small">{{ $r->tin ?: '—' }}</td>
            <td class="text-end font-monospace">{{ number_format($r->total, 2) }}</td>
            <td>@if($r->voided)<span class="badge bg-secondary">Voided</span>@endif</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No invoices in this period.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection

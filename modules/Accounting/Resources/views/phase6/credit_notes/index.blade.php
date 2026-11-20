@extends('accounting::phase2._page')

@php $pageTitle = 'Credit Notes'; $pageSub = 'Return credit notes are created automatically with each sale return; adjustment credit notes are raised from an invoice'; @endphp

@section('page_body')
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
<ul class="nav nav-pills mb-3 small">
    <li class="nav-item"><a class="nav-link {{ !request('type') ? 'active' : '' }}" href="{{ url('accounting/phase6/credit-notes') }}">All</a></li>
    <li class="nav-item"><a class="nav-link {{ request('type') === 'return' ? 'active' : '' }}" href="{{ url('accounting/phase6/credit-notes?type=return') }}">Goods returned</a></li>
    <li class="nav-item"><a class="nav-link {{ request('type') === 'adjustment' ? 'active' : '' }}" href="{{ url('accounting/phase6/credit-notes?type=adjustment') }}">Price adjustments</a></li>
</ul>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr><th>Number</th><th>Date</th><th>Type</th><th>Invoice</th><th>Customer</th><th>Reason</th><th class="text-end">Goods</th><th class="text-end">SSCL</th><th class="text-end">VAT</th><th class="text-end">Total</th><th></th></tr></thead>
    <tbody>
    @forelse($notes as $cn)
        <tr class="{{ $cn->isVoid() ? 'text-muted text-decoration-line-through' : '' }}">
            <td class="fw-semibold">{{ $cn->credit_note_number }}</td>
            <td class="text-nowrap">{{ $cn->credit_note_date?->format('Y-m-d') }}</td>
            <td>{{ $cn->type === 'return' ? 'Goods returned' : 'Price adjustment' }}</td>
            <td><a href="{{ url('accounting/phase6/invoices/' . $cn->order_id) }}">{{ $cn->order?->invoice_number }}</a></td>
            <td class="small">{{ $cn->customer?->business_name ?: $cn->customer?->name }}</td>
            <td class="small">{{ $cn->reason }}</td>
            <td class="text-end font-monospace">{{ number_format($cn->goods_amount, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($cn->sscl_amount, 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($cn->vat_amount, 2) }}</td>
            <td class="text-end font-monospace fw-semibold">{{ number_format($cn->total, 2) }}</td>
            <td class="text-nowrap">
                <a href="{{ url('accounting/phase6/credit-notes/' . $cn->id . '/pdf') }}" class="btn btn-outline-secondary btn-xs"><i class="bi bi-filetype-pdf"></i> PDF</a>
                @if(!$cn->isVoid() && $cn->type === 'adjustment')
                    <form method="POST" action="{{ url('accounting/phase6/credit-notes/' . $cn->id . '/void') }}" class="d-inline" onsubmit="var r = prompt('Reason for voiding this credit note?'); if (!r) return false; this.reason.value = r; return true;">
                        @csrf<input type="hidden" name="reason"><button class="btn btn-outline-danger btn-xs"><i class="bi bi-x-octagon"></i> Void</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="11" class="text-muted">No credit notes yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@if(method_exists($notes, 'links')) {{ $notes->links() }} @endif
@endsection

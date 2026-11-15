@extends('accounting::phase2._page')

@php $pageTitle = 'Supplier Ledger'; $pageSub = 'Payables · LKR'; @endphp

@section('page_actions')
<a href="{{ url('report/accounting/accounts_payable_ageing_summary') }}" class="btn btn-sm btn-outline-secondary">AP Ageing</a>
@endsection

@section('page_body')
<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <select name="supplier_id" class="form-select form-select-sm">
            <option value="">Select supplier…</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" @selected($supplierId==$s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Load</button></div>
</form>

@if($ledger)
<div class="mb-3">
    Bills: {{ $ledger['bill_total'] }} · Payments: {{ $ledger['paid_total'] }}
    · Allocated: {{ $ledger['allocated'] }} · <strong>Outstanding: {{ $ledger['outstanding'] }}</strong>
</div>
<h2 class="h6">Bills</h2>
<table class="table table-sm mb-4">
    <thead><tr><th>ID</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
    <tbody>
    @foreach($ledger['bills'] as $b)
        <tr>
            <td>{{ $b->id }}</td>
            <td>{{ $b->created_at ?? $b->purchase_date ?? '' }}</td>
            <td class="text-end">{{ number_format((float)($b->final_total ?? $b->total ?? 0), 2) }}</td>
            <td>{{ $b->status ?? $b->payment_status ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
@endsection

@extends('accounting::phase2._page')

@php $pageTitle = 'Customer Ledger'; $pageSub = 'Receivables · LKR'; @endphp

@section('page_actions')
<a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}" class="btn btn-sm btn-outline-secondary">AR Ageing</a>
@endsection

@section('page_body')
<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <select name="customer_id" class="form-select form-select-sm">
            <option value="">Select customer…</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected($customerId==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Load</button></div>
</form>

@if($ledger)
<div class="mb-3">
    Invoices: {{ $ledger['invoice_total'] }} · Payments: {{ $ledger['paid_total'] }}
    · Allocated: {{ $ledger['allocated'] }} · <strong>Outstanding: {{ $ledger['outstanding'] }}</strong>
</div>
<h2 class="h6">Invoices</h2>
<table class="table table-sm mb-4">
    <thead><tr><th>ID</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
    <tbody>
    @foreach($ledger['orders'] as $o)
        <tr>
            <td>{{ $o->id }}</td>
            <td>{{ $o->created_at ?? $o->order_date ?? '' }}</td>
            <td class="text-end">{{ number_format((float)($o->final_total ?? $o->total ?? 0), 2) }}</td>
            <td>{{ $o->status ?? $o->payment_status ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<h2 class="h6">Payments</h2>
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Date</th><th class="text-end">Amount</th><th>Method</th></tr></thead>
    <tbody>
    @foreach($ledger['payments'] as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->created_at ?? $p->payment_date ?? '' }}</td>
            <td class="text-end">{{ number_format((float)($p->amount ?? 0), 2) }}</td>
            <td>{{ $p->payment_method ?? $p->method ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
@endsection

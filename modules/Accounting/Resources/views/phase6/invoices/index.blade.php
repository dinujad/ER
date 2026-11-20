@extends('accounting::phase2._page')

@php $pageTitle = 'Sales Invoices'; $pageSub = 'Click any invoice to see lines, tax, payments, journal entries and history'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/receivables/overdue') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-alarm"></i> Overdue &amp; reminders</a>
    <a href="{{ url('accounting/phase6/credit-notes') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-minus"></i> Credit notes</a>
@endsection

@section('page_body')
@php
    $tabs = ['all' => 'All', 'draft' => 'Draft', 'awaiting_payment' => 'Awaiting payment', 'overdue' => 'Overdue', 'paid' => 'Paid', 'voided' => 'Voided'];
@endphp
<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="status" value="{{ $status }}">
    <div class="col-md-3">
        <label class="form-label small mb-0">Customer</label>
        <select name="customer_id" class="form-select form-select-sm">
            <option value="">All customers</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected(($f['customer_id'] ?? null) == $c->id)>{{ $c->business_name ?: $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label small mb-0">From</label><input type="date" name="from" value="{{ $f['from'] ?? '' }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-0">To</label><input type="date" name="to" value="{{ $f['to'] ?? '' }}" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label small mb-0">Search</label><input type="text" name="q" value="{{ $f['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Invoice / order no., customer"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button></div>
</form>

<div class="small text-muted mb-2">{{ number_format($summary['count']) }} invoice(s) · billed Rs {{ number_format($summary['billed'], 2) }}</div>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Invoice</th><th>Date</th><th>Due</th><th>Customer</th><th>Sales person</th>
            <th class="text-end">Goods</th><th class="text-end">SSCL + VAT</th><th class="text-end">Total</th>
            <th class="text-end">Balance due</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
    @forelse($orders as $o)
        @php $st = $o->invoiceStatus(); $due = $o->invoiceDueDate(); @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ url('accounting/phase6/invoices/' . $o->id) }}'">
            <td>
                <a href="{{ url('accounting/phase6/invoices/' . $o->id) }}" class="fw-semibold">{{ $o->invoice_number ?: '—' }}</a>
                <div class="small text-muted">{{ $o->order_number }}</div>
            </td>
            <td class="small text-nowrap">{{ $o->invoiceDate()?->format('Y-m-d') }}</td>
            <td class="small text-nowrap {{ $st === 'overdue' ? 'text-danger fw-semibold' : '' }}">
                {{ $o->isInvoiceIssued() ? $due?->format('Y-m-d') : '—' }}
                @if($st === 'overdue')<div>{{ $o->daysOverdue() }} days late</div>@endif
            </td>
            <td class="small">{{ $o->customer?->business_name ?: ($o->customer?->name ?? '—') }}@if(filled($o->customer?->tin))<div class="text-muted">TIN {{ $o->customer->tin }}</div>@endif</td>
            <td class="small">{{ $o->salesPerson?->name ?? '—' }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $o->total_amount, 2) }}</td>
            <td class="text-end font-monospace">{{ $o->chargesInvoiceTax() ? number_format($o->invoice_tax_amount, 2) : '—' }}</td>
            <td class="text-end font-monospace fw-semibold">{{ number_format($o->invoice_total, 2) }}</td>
            <td class="text-end font-monospace {{ $o->due_amount > 0 ? 'text-danger' : 'text-muted' }}">{{ $st === 'voided' ? '—' : number_format($o->due_amount, 2) }}</td>
            <td><span class="badge {{ $o->invoiceStatusBadge() }}">{{ $o->invoiceStatusLabel() }}</span></td>
        </tr>
    @empty
        <tr><td colspan="10" class="text-muted">No invoices match these filters.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $orders->links() }}
@endsection

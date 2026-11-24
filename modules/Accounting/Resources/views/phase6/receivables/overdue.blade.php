@extends('accounting::phase2._page')

@php $pageTitle = 'Overdue Invoices'; $pageSub = 'Past their due date and not fully paid — oldest first'; $grand = round(array_sum($totals->all()), 2); @endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/invoices?status=overdue') }}" class="btn btn-outline-secondary btn-sm">Invoice list</a>
    <a href="{{ url('report/accounting/accounts_receivable_ageing_summary') }}" class="btn btn-outline-secondary btn-sm">AR ageing report</a>
@endsection

@section('page_body')

<div class="row g-2 mb-3">
    @foreach($buckets as $b)
        <div class="col-6 col-md-2"><div class="card"><div class="card-body py-2">
            <div class="small text-muted">{{ $b }}</div><div class="fw-bold">{{ number_format($totals[$b], 2) }}</div>
        </div></div></div>
    @endforeach
    <div class="col-6 col-md-2"><div class="card border-danger"><div class="card-body py-2">
        <div class="small text-muted">Total overdue</div><div class="fw-bold text-danger">{{ number_format($grand, 2) }}</div>
    </div></div></div>
</div>

<form method="POST" action="{{ url('accounting/phase6/receivables/reminders') }}" onsubmit="return confirm('Send SMS reminders to the selected customers?');">
    @csrf
    <div class="d-flex justify-content-between mb-2">
        <div class="small text-muted">{{ $rows->count() }} overdue invoice(s) · {{ $byCustomer->count() }} customer(s)</div>
        <button class="btn btn-sm btn-outline-info"><i class="bi bi-bell"></i> Send SMS reminder to selected</button>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr>
            <th><input type="checkbox" onclick="document.querySelectorAll('.od-sel').forEach(c => c.checked = this.checked)"></th>
            <th>Invoice</th><th>Customer</th><th>Phone</th><th>Due date</th><th class="text-end">Days late</th><th>Bucket</th>
            <th class="text-end">Total</th><th class="text-end">Balance due</th><th>Last reminder</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($rows as $o)
            <tr>
                <td><input type="checkbox" class="od-sel" name="order_ids[]" value="{{ $o->id }}" @disabled(!filled($o->customer?->phone))></td>
                <td><a href="{{ url('accounting/phase6/invoices/' . $o->id) }}">{{ $o->invoice_number }}</a></td>
                <td class="small">{{ $o->customer?->business_name ?: $o->customer?->name }}</td>
                <td class="small">{{ $o->customer?->phone ?: '—' }}</td>
                <td class="text-nowrap">{{ $o->invoiceDueDate()?->format('Y-m-d') }}</td>
                <td class="text-end text-danger fw-semibold">{{ $o->daysOverdue() }}</td>
                <td class="small">{{ $o->ageingBucket() }}</td>
                <td class="text-end font-monospace">{{ number_format($o->invoice_total, 2) }}</td>
                <td class="text-end font-monospace text-danger">{{ number_format((float) $o->due_amount, 2) }}</td>
                <td class="small text-muted">{{ $o->last_reminder_at?->format('Y-m-d') ?? '—' }}</td>
                <td class="text-end"><a href="{{ url('accounting/phase6/invoices/' . $o->id) }}" class="btn btn-outline-secondary btn-xs"><i class="bi bi-eye"></i> Open</a></td>
            </tr>
        @empty
            <tr><td colspan="11" class="text-success">Nothing is overdue.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</form>
@endsection

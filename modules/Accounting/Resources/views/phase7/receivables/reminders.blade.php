@extends('accounting::phase2._page')

@php $pageTitle = 'Payment Reminders'; $pageSub = 'Automatic SMS schedule and every reminder sent'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/receivables/overdue') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-alarm"></i> Overdue invoices</a>
@endsection

@section('page_body')
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

<div class="row g-3 mb-3">
    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="fw-semibold">Schedule</div>
                <div class="small">
                    Automatic reminders are <strong class="{{ config('dermas.auto_reminders') ? 'text-success' : 'text-muted' }}">{{ config('dermas.auto_reminders') ? 'ON — runs daily at 09:30' : 'OFF' }}</strong>.
                    An SMS goes out when an invoice is
                    <strong>{{ implode(', ', config('dermas.reminder_schedule_days', [])) }}</strong> day(s) past due,
                    never closer than {{ config('dermas.reminder_min_gap_days', 5) }} days to the previous one.
                </div>
                <div class="small text-muted mt-1">Change with <code>DERMAS_AUTO_REMINDERS</code>, <code>DERMAS_REMINDER_DAYS</code> and <code>DERMAS_REMINDER_MIN_GAP_DAYS</code> in .env.</div>
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div class="small">Due today: <strong>{{ $preview['sent'] }}</strong> reminder(s) out of {{ $preview['checked'] }} overdue invoice(s).</div>
            <form method="POST" action="{{ url('accounting/phase7/reminders/run') }}" onsubmit="return confirm('Send {{ $preview['sent'] }} SMS reminder(s) now?');">@csrf
                <button class="btn btn-primary btn-sm" @disabled($preview['sent'] === 0)><i class="bi bi-send"></i> Send due reminders now</button>
            </form>
        </div>
    </div></div></div>
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <div class="fw-semibold mb-1">Last 30 days</div>
        @foreach(['sent' => 'text-success', 'failed' => 'text-danger', 'skipped' => 'text-muted'] as $k => $cls)
            <div class="d-flex justify-content-between small"><span class="{{ $cls }}">{{ ucfirst($k) }}</span><strong>{{ $stats[$k] ?? 0 }}</strong></div>
        @endforeach
    </div></div></div>
</div>

<ul class="nav nav-pills small mb-2">
    @foreach(['' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'skipped' => 'Skipped'] as $k => $label)
        <li class="nav-item"><a class="nav-link {{ (string) $status === $k ? 'active' : '' }}" href="{{ url('accounting/phase7/reminders' . ($k ? '?status=' . $k : '')) }}">{{ $label }}</a></li>
    @endforeach
</ul>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr><th>When</th><th>Invoice</th><th>Customer</th><th>To</th><th class="text-end">Days late</th><th>How</th><th>Result</th><th>Message</th></tr></thead>
    <tbody>
    @forelse($logs as $l)
        <tr>
            <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($l->created_at)->format('Y-m-d H:i') }}</td>
            <td><a href="{{ url('accounting/phase6/invoices/' . $l->order_id) }}">{{ $l->invoice_number }}</a></td>
            <td class="small">{{ $l->customer }}</td>
            <td class="small">{{ $l->recipient ?: '—' }}</td>
            <td class="text-end">{{ $l->days_overdue }}</td>
            <td class="small">{{ $l->trigger === 'schedule' ? 'Automatic' : 'Manual' }}</td>
            <td><span class="badge {{ $l->status === 'sent' ? 'bg-success' : ($l->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">{{ $l->status }}</span>@if($l->error)<div class="small text-danger">{{ $l->error }}</div>@endif</td>
            <td class="small text-muted" style="max-width:340px">{{ \Illuminate\Support\Str::limit($l->message, 120) }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No reminders yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@if(method_exists($logs, 'links')) {{ $logs->links() }} @endif
@endsection

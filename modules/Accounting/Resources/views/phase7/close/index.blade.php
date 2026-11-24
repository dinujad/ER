@extends('accounting::phase2._page')

@php
    $pageTitle = 'Month-end Close — ' . $period['label'];
    $pageSub = 'Run the checks, review, sign off, then lock the month';
    $icons = ['pass' => ['bi-check-circle-fill', 'text-success'], 'warn' => ['bi-exclamation-triangle-fill', 'text-warning'], 'fail' => ['bi-x-circle-fill', 'text-danger']];
    $failed = collect($checks)->where('status', 'fail')->count();
    $locked = $periodRow && $periodRow->isClosed();
    $prev = \Illuminate\Support\Carbon::parse($period['from'])->subMonthNoOverflow()->format('Y-m');
    $next = \Illuminate\Support\Carbon::parse($period['from'])->addMonthNoOverflow()->format('Y-m');
@endphp

@section('page_actions')
    <a href="{{ url('accounting/phase7/close?period=' . $prev) }}" class="btn btn-outline-secondary btn-sm">‹ {{ \Illuminate\Support\Carbon::parse($prev . '-01')->format('M Y') }}</a>
    <form method="GET" class="d-flex"><input type="month" name="period" value="{{ $key }}" class="form-control form-control-sm" onchange="this.form.submit()"></form>
    <a href="{{ url('accounting/phase7/close?period=' . $next) }}" class="btn btn-outline-secondary btn-sm">{{ \Illuminate\Support\Carbon::parse($next . '-01')->format('M Y') }} ›</a>
@endsection

@section('page_body')

<div class="d-flex flex-wrap gap-3 align-items-center mb-3">
    @foreach(['Checks' => true, 'Signed off' => in_array($review->status, ['signed_off', 'locked'], true), 'Locked' => $locked] as $label => $done)
        <span class="{{ $done ? 'text-success fw-semibold' : 'text-muted' }}"><i class="bi {{ $done ? 'bi-check-circle-fill' : 'bi-circle' }}"></i> {{ $label }}</span>
        @if(!$loop->last)<i class="bi bi-chevron-right text-muted small"></i>@endif
    @endforeach
    <span class="ms-auto small {{ $failed ? 'text-danger' : 'text-success' }}">{{ $failed ? $failed . ' check(s) need attention' : 'All checks passed' }}</span>
</div>

<div class="card mb-3"><div class="list-group list-group-flush">
    @foreach($checks as $c)
        <div class="list-group-item d-flex gap-3 align-items-start">
            <i class="bi {{ $icons[$c['status']][0] }} {{ $icons[$c['status']][1] }} fs-5"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $c['title'] }}</div>
                <div class="small text-muted">{{ $c['detail'] }}</div>
            </div>
            @if($c['url'])<a href="{{ $c['url'] }}" class="btn btn-sm btn-outline-secondary text-nowrap">Review</a>@endif
        </div>
    @endforeach
</div></div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            @if($locked)
                <div class="text-success mb-2"><i class="bi bi-lock-fill"></i> {{ $period['label'] }} is locked{{ $review->locked_at ? ' since ' . \Illuminate\Support\Carbon::parse($review->locked_at)->format('Y-m-d H:i') : '' }}{{ isset($users[$review->locked_by ?? 0]) ? ' by ' . $users[$review->locked_by] : '' }}. Nothing can be posted into it.</div>
                <form method="POST" action="{{ url('accounting/phase7/close/' . $key . '/reopen') }}" class="d-flex gap-2">@csrf
                    <input name="reason" class="form-control form-control-sm" placeholder="Reason for reopening (kept in the history)" required>
                    <button class="btn btn-outline-danger btn-sm text-nowrap">Reopen period</button>
                </form>
            @elseif($review->status === 'signed_off')
                <div class="mb-2">Signed off {{ $review->signed_off_at ? \Illuminate\Support\Carbon::parse($review->signed_off_at)->format('Y-m-d H:i') : '' }}{{ isset($users[$review->signed_off_by ?? 0]) ? ' by ' . $users[$review->signed_off_by] : '' }}.</div>
                <form method="POST" action="{{ url('accounting/phase7/close/' . $key . '/lock') }}" onsubmit="return confirm('Lock {{ $period['label'] }}? Invoices, payments and journals dated in it will be blocked until it is reopened.');">@csrf
                    <button class="btn btn-danger btn-sm"><i class="bi bi-lock"></i> Lock {{ $period['label'] }}</button>
                </form>
            @else
                <form method="POST" action="{{ url('accounting/phase7/close/' . $key . '/sign-off') }}">@csrf
                    <label class="form-label small mb-0">Review notes</label>
                    <textarea name="notes" class="form-control form-control-sm mb-2" rows="3" placeholder="What was checked, any adjustments made, accepted exceptions…">{{ old('notes', $review->notes) }}</textarea>
                    @if($failed)
                        <div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="accept_exceptions" value="1" id="acc"><label for="acc" class="form-check-label">I reviewed the {{ $failed }} failed check(s) and accept them (explained in the notes)</label></div>
                    @endif
                    <button class="btn btn-primary btn-sm"><i class="bi bi-pen"></i> Sign off checklist</button>
                </form>
            @endif
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><strong>Recent closes</strong></div><div class="card-body p-0">
            <table class="table table-sm mb-0"><tbody>
            @forelse($history as $h)
                <tr>
                    <td><a href="{{ url('accounting/phase7/close?period=' . $h->period_key) }}">{{ \Illuminate\Support\Carbon::parse($h->period_key . '-01')->format('M Y') }}</a></td>
                    <td><span class="badge {{ $h->status === 'locked' ? 'bg-success' : ($h->status === 'signed_off' ? 'bg-primary' : 'bg-secondary') }}">{{ str_replace('_', ' ', $h->status) }}</span></td>
                    <td class="small text-muted">{{ $h->signed_off_at ? \Illuminate\Support\Carbon::parse($h->signed_off_at)->format('Y-m-d') : '' }}</td>
                </tr>
            @empty
                <tr><td class="text-muted small">No months closed yet.</td></tr>
            @endforelse
            </tbody></table>
        </div></div>
    </div>
</div>
@endsection

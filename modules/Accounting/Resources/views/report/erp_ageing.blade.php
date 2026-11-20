@extends('accounting::phase2._page')
@php
    $pageTitle = $title ?? 'Ageing Report';
    $pageSub = ($start_date ?? '').' → '.($end_date ?? '');
    $buckets = collect($rows ?? [])->groupBy('bucket');
@endphp

@section('page_actions')
    @if(!empty($ledgerUrl))
        <a class="btn btn-sm btn-outline-secondary" href="{{ $ledgerUrl }}">Open ledger</a>
    @endif
@endsection

@section('page_body')
<form method="get" action="{{ request()->url() }}" class="acct-toolbar" aria-label="Ageing filters">
    <div>
        <label class="form-label">From</label>
        <input type="date" name="start_date" value="{{ $start_date }}" class="form-control form-control-sm">
    </div>
    <div>
        <label class="form-label">To</label>
        <input type="date" name="end_date" value="{{ $end_date }}" class="form-control form-control-sm">
    </div>
    <button type="submit" class="btn btn-sm btn-primary">Run</button>
</form>

<div class="acct-kpi-grid">
    @foreach(['0-30','31-60','61-90','90+'] as $b)
        @php $sum = (float) ($buckets[$b] ?? collect())->sum('due'); @endphp
        <div class="acct-kpi">
            <div class="acct-kpi-label">{{ $b }} days</div>
            <div class="acct-kpi-value" style="font-size:1.1rem;margin-top:8px">LKR {{ number_format($sum, 2) }}</div>
            <div class="acct-kpi-meta">{{ ($buckets[$b] ?? collect())->count() }} open docs</div>
        </div>
    @endforeach
</div>

<div class="acct-panel">
    <div class="acct-panel-header">
        <h2>{{ $title }}</h2>
        <span class="acct-badge info">{{ count($rows) }} rows · LKR</span>
    </div>
    <div class="acct-table-wrap">
        @if(empty($rows) || count($rows) === 0)
            @include('accounting::components.empty_state', [
                'title' => 'No outstanding balances',
                'message' => 'No unpaid '.$kind.' documents in this period.',
                'actionUrl' => $ledgerUrl ?? url('accounting/dashboard'),
                'actionLabel' => 'Back',
            ])
        @else
            <table class="table acct-fin-table mb-0">
                <thead>
                    <tr>
                        <th>Party</th>
                        <th>Ref</th>
                        <th>Date</th>
                        <th>Bucket</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r->party }}</td>
                            <td>{{ $r->ref }}</td>
                            <td>{{ $r->date }}</td>
                            <td><span class="acct-badge">{{ $r->bucket }}</span></td>
                            <td class="acct-amt">{{ number_format((float)$r->total, 2) }}</td>
                            <td class="acct-amt">{{ number_format((float)$r->paid, 2) }}</td>
                            <td class="acct-amt">{{ number_format((float)$r->due, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

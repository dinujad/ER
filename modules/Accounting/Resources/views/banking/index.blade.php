@extends('accounting::phase2._page')

@php $pageTitle = 'Bank Accounts'; $pageSub = 'Each account: import statements, match, reconcile — all in one place'; $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 2); @endphp

@section('page_actions')
    <a href="{{ url('accounting/banking/create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Add bank account</a>
    <a href="{{ url('accounting/banking' . ($showArchived ? '' : '?archived=1')) }}" class="btn btn-outline-secondary btn-sm">{{ $showArchived ? 'Hide archived' : 'Show archived' }}</a>
@endsection

@section('page_body')

@if($accounts->isEmpty())
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-bank fs-1 text-muted"></i>
        <div class="fw-semibold mt-2">No bank accounts yet</div>
        <div class="small text-muted mb-3">Add each bank or credit-card account once. Then import its statements and reconcile it every month.</div>
        <a href="{{ url('accounting/banking/create') }}" class="btn btn-primary btn-sm">Add bank account</a>
    </div></div>
@else
<div class="table-responsive">
<table class="table table-hover align-middle">
    <thead class="table-light"><tr>
        <th>Account</th><th>Number</th><th class="text-end">Book balance</th><th class="text-end">Latest statement</th>
        <th class="text-end">To match</th><th>Last reconciled</th><th></th>
    </tr></thead>
    <tbody>
    @foreach($accounts as $a)
        @php $gap = $a->statement_balance !== null ? round($a->statement_balance - $a->book_balance, 2) : null; @endphp
        <tr class="{{ $a->status === 'archived' ? 'text-muted' : '' }}" style="cursor:pointer" onclick="window.location='{{ url('accounting/banking/' . $a->id) }}'">
            <td>
                <a href="{{ url('accounting/banking/' . $a->id) }}" class="fw-semibold">{{ $a->display_name }}</a>
                <div class="small text-muted">{{ $a->bank_name }} · {{ \App\Services\Accounting\BankAccountService::TYPES[$a->account_type] ?? $a->account_type }} · GL {{ $a->gl_code }}
                    @if($a->status === 'archived')<span class="badge bg-secondary">Archived</span>@endif</div>
            </td>
            <td class="font-monospace small">{{ $a->masked }}</td>
            <td class="text-end font-monospace">{{ $fmt($a->book_balance) }}</td>
            <td class="text-end font-monospace">{{ $fmt($a->statement_balance) }}
                <div class="small text-muted">{{ $a->statement_date ? \Illuminate\Support\Carbon::parse($a->statement_date)->format('d M Y') : 'no statement yet' }}</div>
                @if($gap !== null && abs($gap) > 0.009)<div class="small text-danger">differs by {{ number_format($gap, 2) }}</div>@endif
            </td>
            <td class="text-end">@if($a->unmatched)<a href="{{ url('accounting/phase7/reconcile?account_id=' . $a->chart_of_account_id) }}" class="badge bg-warning text-dark" onclick="event.stopPropagation()">{{ $a->unmatched }}</a>@else<span class="text-success small">0</span>@endif</td>
            <td class="small">{{ $a->last_reconciled ? \Illuminate\Support\Carbon::parse($a->last_reconciled)->format('d M Y') : 'never' }}</td>
            <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif
@endsection

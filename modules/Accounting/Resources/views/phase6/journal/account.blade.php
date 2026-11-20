@extends('accounting::phase2._page')

@php $pageTitle = 'Account Transactions'; $pageSub = 'Every journal line on an account, with the document that created it'; @endphp

@section('page_actions')
    @if($activity && $activity['account'])
        <a href="{{ url('accounting/phase6/recode?account_id=' . $accountId . '&from=' . $from . '&to=' . $to) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left-right"></i> Find &amp; recode</a>
    @endif
@endsection

@section('page_body')
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <select name="account_id" class="form-select form-select-sm" required>
            <option value="">Select account…</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected($accountId == $a->id)>{{ $a->gl_code }} — {{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Show</button></div>
</form>

@if($activity && $activity['account'])
    <div class="mb-2"><strong>{{ $activity['account']->gl_code }} {{ $activity['account']->name }}</strong>
        · Opening {{ number_format($activity['opening'], 2) }}
        · Debits {{ number_format($activity['debit'], 2) }} · Credits {{ number_format($activity['credit'], 2) }}
        · Closing <strong>{{ number_format($activity['closing'], 2) }}</strong></div>
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr><th>Date</th><th>Journal</th><th>Description</th><th>Source document</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
        <tr class="text-muted"><td colspan="6">Opening balance</td><td class="text-end font-monospace">{{ number_format($activity['opening'], 2) }}</td></tr>
        @forelse($activity['rows'] as $r)
            <tr>
                <td class="text-nowrap small">{{ $r->date }}</td>
                <td class="small"><a href="{{ url('accounting/phase6/journal/' . urlencode($r->transaction_number)) }}">{{ $r->transaction_number }}</a></td>
                <td class="small">{{ $r->description }}@if($r->reference)<div class="text-muted">{{ $r->reference }}</div>@endif</td>
                <td class="small">@if($r->source['url'])<a href="{{ $r->source['url'] }}">{{ $r->source['label'] }}</a>@else{{ $r->source['label'] }}@endif</td>
                <td class="text-end font-monospace">{{ $r->debit > 0 ? number_format($r->debit, 2) : '' }}</td>
                <td class="text-end font-monospace">{{ $r->credit > 0 ? number_format($r->credit, 2) : '' }}</td>
                <td class="text-end font-monospace">{{ number_format($r->balance, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-muted">No activity in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
@endif
@endsection

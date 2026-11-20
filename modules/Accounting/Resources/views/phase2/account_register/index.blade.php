@extends('accounting::phase2._page')

@php $pageTitle = 'Account Register'; $pageSub = 'Running balances · LKR'; @endphp

@section('page_actions')
@if($accountId)
<a href="{{ url('accounting/account-register/export?account_id='.$accountId.'&from='.$from.'&to='.$to) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
@endif
@endsection

@section('page_body')
<form method="get" class="row g-2 mb-3">
    <div class="col-md-5">
        <select name="account_id" class="form-select form-select-sm" required>
            <option value="">Select account…</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected($accountId==$a->id)>{{ $a->gl_code }} — {{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary">Load</button></div>
</form>

@if($register && $account)
<div class="mb-2"><strong>{{ $account->gl_code }}</strong> {{ $account->name }}
    · Opening: {{ $register['opening'] }} · Closing: {{ $register['closing'] }}
    · Normal: {{ $register['normal'] }}
</div>
<div class="table-responsive">
<table class="table table-sm table-hover">
    <thead><tr><th>Date</th><th>Journal #</th><th>Description</th><th>Source</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($register['rows'] as $row)
        <tr>
            <td>{{ $row->date }}</td>
            <td><a href="{{ url('accounting/phase6/journal/' . urlencode($row->transaction_number)) }}">{{ $row->transaction_number }}</a></td>
            <td>{{ $row->description }}</td>
            @php $src = \App\Services\Accounting\SourceDocumentResolver::resolve($row->source_type ?? null, $row->source_id ?? null); @endphp
            <td class="small">@if(!empty($row->source_type))@if($src['url'])<a href="{{ $src['url'] }}">{{ $src['label'] }}</a>@else{{ $src['label'] }}@endif @endif</td>
            <td class="text-end">{{ $row->debit }}</td>
            <td class="text-end">{{ $row->credit }}</td>
            <td class="text-end">{{ $row->running_balance }}</td>
            <td>{{ $row->status }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No posted activity in range.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endif
@endsection

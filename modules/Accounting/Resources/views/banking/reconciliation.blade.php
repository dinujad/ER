@extends('accounting::phase2._page')

@php $pageTitle = 'Reconciliation — ' . $acc->display_name; $pageSub = 'Statement date ' . \Illuminate\Support\Carbon::parse($rec->statement_date)->format('d M Y') . ' · ' . $rec->status; @endphp

@section('page_actions')
    <a href="{{ url('accounting/banking/' . $acc->id . '?tab=history') }}" class="btn btn-outline-secondary btn-sm">History</a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
@endsection

@section('page_body')
<div class="card mb-3"><div class="card-body small d-flex flex-wrap gap-4">
    <div>Beginning balance <strong>{{ number_format((float) $rec->beginning_balance, 2) }}</strong></div>
    <div>Cleared ({{ $rec->cleared_count }}) <strong>{{ number_format((float) $rec->cleared_total, 2) }}</strong></div>
    <div>Ending balance <strong>{{ number_format((float) $rec->statement_balance, 2) }}</strong></div>
    <div>Difference <strong class="text-success">0.00</strong></div>
    <div>Reconciled by {{ $by ?? '—' }} {{ $rec->completed_at ? 'on ' . \Illuminate\Support\Carbon::parse($rec->completed_at)->format('Y-m-d H:i') : '' }}</div>
    @if($rec->notes)<div class="w-100 text-muted">{{ $rec->notes }}</div>@endif
</div></div>
<div class="table-responsive">
<table class="table table-sm align-middle">
    <thead class="table-light"><tr><th>Date</th><th>Description</th><th>Journal</th><th>Source</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    @foreach($lines as $l)
        @php $src = \App\Services\Accounting\SourceDocumentResolver::resolve($l->source_type, $l->source_id); @endphp
        <tr>
            <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($l->date)->format('d M Y') }}</td>
            <td class="small">{{ $l->name }}</td>
            <td class="small"><a href="{{ url('accounting/phase6/journal/' . urlencode($l->transaction_number)) }}">{{ $l->transaction_number }}</a></td>
            <td class="small">@if($src['url'])<a href="{{ $src['url'] }}">{{ $src['label'] }}</a>@else{{ $src['label'] }}@endif</td>
            <td class="text-end font-monospace">{{ number_format((float) $l->amount, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection

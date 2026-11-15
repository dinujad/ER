@extends('accounting::phase2._page')

@php $pageTitle = $journal->journal_number; $pageSub = ucfirst($journal->status).' · LKR'; @endphp

@section('page_actions')
    @if(in_array($journal->status, ['draft','rejected']))
        <a href="{{ url('accounting/manual-journals/'.$journal->id.'/edit') }}" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="post" action="{{ url('accounting/manual-journals/'.$journal->id.'/submit') }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Submit</button></form>
    @endif
    @if($journal->status==='submitted')
        <form method="post" action="{{ url('accounting/manual-journals/'.$journal->id.'/approve') }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
        <form method="post" action="{{ url('accounting/manual-journals/'.$journal->id.'/reject') }}" class="d-inline">@csrf
            <input type="hidden" name="reason" value="Rejected by reviewer">
            <button class="btn btn-sm btn-outline-danger">Reject</button>
        </form>
    @endif
    @if(in_array($journal->status, ['approved','draft']) )
        <form method="post" action="{{ url('accounting/manual-journals/'.$journal->id.'/post') }}" class="d-inline">@csrf<button class="btn btn-sm btn-dark">Post</button></form>
    @endif
    @if($journal->status==='posted')
        <form method="post" action="{{ url('accounting/manual-journals/'.$journal->id.'/reverse') }}" class="d-inline" onsubmit="return confirm('Reverse this posted journal?')">@csrf<button class="btn btn-sm btn-warning">Reverse</button></form>
    @endif
@endsection

@section('page_body')
<div class="mb-3 small text-muted">
    Date: {{ optional($journal->journal_date)->format('Y-m-d') }}
    @if($journal->reference) · Ref: {{ $journal->reference }} @endif
    @if($journal->transaction_number) · TXN: {{ $journal->transaction_number }} @endif
</div>
<p>{{ $journal->description }}</p>
@if($journal->rejection_reason)<div class="alert alert-warning">{{ $journal->rejection_reason }}</div>@endif

<table class="table table-sm">
    <thead><tr><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th>Memo</th></tr></thead>
    <tbody>
    @foreach($journal->lines as $line)
        <tr>
            <td>{{ optional($line->account)->gl_code }} — {{ optional($line->account)->name }}</td>
            <td class="text-end">{{ number_format((float)$line->debit, 2) }}</td>
            <td class="text-end">{{ number_format((float)$line->credit, 2) }}</td>
            <td>{{ $line->memo }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>Totals</th>
            <th class="text-end">{{ $journal->totalDebit() }}</th>
            <th class="text-end">{{ $journal->totalCredit() }}</th>
            <th>{{ $journal->isBalanced() ? 'Balanced' : 'Unbalanced' }}</th>
        </tr>
    </tfoot>
</table>
@endsection

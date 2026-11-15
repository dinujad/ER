@extends('accounting::phase2._page')

@php $pageTitle = 'Recurring Transactions'; $pageSub = 'Journals · Invoices · Bills'; @endphp

@section('page_actions')
<a href="{{ url('accounting/recurring-journals/create?document_type=journal') }}" class="btn btn-sm btn-primary">New Journal</a>
<a href="{{ url('accounting/recurring-journals/create?document_type=invoice') }}" class="btn btn-sm btn-outline-primary">New Invoice</a>
<a href="{{ url('accounting/recurring-journals/create?document_type=bill') }}" class="btn btn-sm btn-outline-primary">New Bill</a>
<a href="{{ url('accounting/recurring-journals/history') }}" class="btn btn-sm btn-outline-secondary">History</a>
@endsection

@section('page_body')
<div class="mb-2">
    <a href="{{ url('accounting/recurring-journals') }}">All</a> ·
    <a href="{{ url('accounting/recurring-journals?document_type=journal') }}">Journals</a> ·
    <a href="{{ url('accounting/recurring-journals?document_type=invoice') }}">Invoices</a> ·
    <a href="{{ url('accounting/recurring-journals?document_type=bill') }}">Bills</a>
</div>
<table class="table table-sm">
    <thead><tr><th>Name</th><th>Type</th><th>Freq</th><th>Next</th><th>Mode</th><th>Status</th><th>Runs</th><th></th></tr></thead>
    <tbody>
    @forelse($templates as $t)
        <tr>
            <td>{{ $t->name }}</td>
            <td>{{ $t->document_type ?? 'journal' }}</td>
            <td>{{ $t->frequency }}</td>
            <td>{{ optional($t->next_run_on)->format('Y-m-d') }}</td>
            <td>{{ $t->execution_mode }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ $t->occurrence_count ?? 0 }}</td>
            <td class="text-nowrap">
                <form method="post" action="{{ url('accounting/recurring-journals/'.$t->id.'/run') }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Run</button></form>
                @if($t->status==='active')
                    <form method="post" action="{{ url('accounting/recurring-journals/'.$t->id.'/pause') }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Pause</button></form>
                @else
                    <form method="post" action="{{ url('accounting/recurring-journals/'.$t->id.'/resume') }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Resume</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No templates.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $templates->links() }}
@endsection

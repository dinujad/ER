@extends('accounting::phase2._page')

@php $pageTitle = 'Recurring Journals'; $pageSub = 'Scheduled templates'; @endphp

@section('page_actions')
<a href="{{ url('accounting/recurring-journals/create') }}" class="btn btn-sm btn-primary">New Template</a>
@endsection

@section('page_body')
<table class="table table-sm">
    <thead><tr><th>Name</th><th>Frequency</th><th>Next</th><th>Mode</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($templates as $t)
        <tr>
            <td>{{ $t->name }}</td>
            <td>{{ $t->frequency }}</td>
            <td>{{ optional($t->next_run_on)->format('Y-m-d') }}</td>
            <td>{{ $t->execution_mode }}</td>
            <td>{{ $t->status }}</td>
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
        <tr><td colspan="6" class="text-muted">No recurring templates.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $templates->links() }}
@endsection

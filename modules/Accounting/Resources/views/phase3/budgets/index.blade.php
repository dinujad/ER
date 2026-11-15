@extends('accounting::phase2._page')
@php $pageTitle = 'Budget versions'; $pageSub = 'Workflow'; @endphp
@section('page_actions')
    <a href="{{ url('accounting/budget-versions/create') }}" class="btn btn-primary btn-sm">New version</a>
    <a href="{{ url('accounting/budget-versions/vs-actual') }}" class="btn btn-outline-secondary btn-sm">Budget vs actual</a>
@endsection
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>Name</th><th>FY</th><th>Ver</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($versions as $v)
        <tr>
            <td>{{ $v->name }}</td>
            <td>{{ $v->financial_year }}</td>
            <td>{{ $v->version_no }}</td>
            <td>{{ $v->status }}</td>
            <td><a href="{{ url('accounting/budget-versions/'.$v->id) }}">Open</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No budget versions.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $versions->links() }}
@endsection
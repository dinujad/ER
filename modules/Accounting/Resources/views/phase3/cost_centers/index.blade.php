@extends('accounting::phase2._page')
@php $pageTitle = 'Cost Centers'; $pageSub = 'Hierarchy'; @endphp
@section('page_actions')
    <a href="{{ url('accounting/cost-centers/create') }}" class="btn btn-primary btn-sm">New center</a>
@endsection
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>Code</th><th>Name</th><th>Parent</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($centers as $cc)
        <tr>
            <td>{{ $cc->code }}</td>
            <td>{{ $cc->name }}</td>
            <td>{{ $cc->parent->code ?? '—' }}</td>
            <td>{{ $cc->status }}</td>
            <td><a href="{{ url('accounting/cost-centers/'.$cc->id.'/edit') }}">Edit</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No cost centers.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $centers->links() }}
@endsection
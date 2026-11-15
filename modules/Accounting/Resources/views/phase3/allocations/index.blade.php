@extends('accounting::phase2._page')
@php $pageTitle = 'Cost Allocations'; @endphp
@section('page_actions')
    <a href="{{ url('accounting/cost-allocations/create') }}" class="btn btn-primary btn-sm">New allocation</a>
@endsection
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>Name</th><th>Date</th><th class="text-end">Source</th><th>Lines</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($allocations as $a)
        <tr>
            <td>{{ $a->name }}</td>
            <td>{{ optional($a->allocation_date)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format((float)$a->source_amount,2) }}</td>
            <td>{{ $a->lines->count() }}</td>
            <td>{{ $a->status }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No allocations.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $allocations->links() }}
@endsection
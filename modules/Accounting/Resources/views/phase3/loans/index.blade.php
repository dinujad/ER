@extends('accounting::phase2._page')
@php $pageTitle = 'Loan Register'; $pageSub = 'Term loans & amortization'; @endphp
@section('page_actions')
    <a href="{{ url('accounting/loans/create') }}" class="btn btn-primary btn-sm">Register loan</a>
@endsection
@section('page_body')
<table class="table table-sm table-hover">
    <thead><tr><th>Number</th><th>Lender</th><th>Status</th><th class="text-end">Principal</th><th class="text-end">Outstanding</th><th></th></tr></thead>
    <tbody>
    @forelse($loans as $loan)
        <tr>
            <td>{{ $loan->loan_number }}</td>
            <td>{{ $loan->lender_name }}</td>
            <td>{{ $loan->status }}</td>
            <td class="text-end">{{ number_format((float)$loan->principal,2) }}</td>
            <td class="text-end">{{ number_format((float)$loan->outstanding_principal,2) }}</td>
            <td><a href="{{ url('accounting/loans/'.$loan->id) }}">View</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No loans registered.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $loans->links() }}
@endsection
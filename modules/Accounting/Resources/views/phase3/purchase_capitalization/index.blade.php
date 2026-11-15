@extends('accounting::phase2._page')
@php $pageTitle = 'Purchase-linked assets'; @endphp
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>ID</th><th>PO</th><th>Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($candidates as $c)
        <tr>
            <td>{{ $c->id }}</td>
            <td>#{{ $c->purchase_id }}</td>
            <td>{{ number_format((float)$c->amount,2) }}</td>
            <td>{{ $c->status }}</td>
            <td><a href="{{ url('accounting/purchase-capitalization/'.$c->id) }}">View</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $candidates instanceof \Illuminate\Pagination\LengthAwarePaginator ? $candidates->links() : '' }}
@endsection
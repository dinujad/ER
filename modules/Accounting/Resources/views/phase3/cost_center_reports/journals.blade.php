@extends('accounting::phase2._page')
@php $pageTitle = 'Cost center journals'; @endphp
@section('page_body')
<table class="table table-sm"><thead><tr><th>Date</th><th>Txn</th><th>Debit</th><th>Credit</th></tr></thead><tbody>
@foreach($entries as $e)
<tr><td>{{ $e->date }}</td><td>{{ $e->transaction_number }}</td><td>{{ $e->debit }}</td><td>{{ $e->credit }}</td></tr>
@endforeach
</tbody></table>
@endsection
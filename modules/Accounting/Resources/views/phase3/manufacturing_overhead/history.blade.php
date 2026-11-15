@extends('accounting::phase2._page')
@php $pageTitle = 'Overhead run history'; @endphp
@section('page_body')
<table class="table table-sm"><thead><tr><th>Run</th><th>Period</th><th>Status</th><th>Allocated</th><th></th></tr></thead><tbody>
@foreach($runs as $run)
<tr><td>#{{ $run->id }}</td><td>{{ $run->period_from }} – {{ $run->period_to }}</td><td>{{ $run->status }}</td>
<td>{{ number_format((float)$run->total_allocated,2) }}</td>
<td>@if($run->status==='posted')<form method="post" action="{{ url('accounting/manufacturing-overhead/runs/'.$run->id.'/reverse') }}">@csrf<button class="btn btn-link btn-sm">Reverse</button></form>@endif</td></tr>
@endforeach
</tbody></table>
@endsection
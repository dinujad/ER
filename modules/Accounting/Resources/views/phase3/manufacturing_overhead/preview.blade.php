@extends('accounting::phase2._page')
@php $pageTitle = 'Overhead preview run #'.$run->id; @endphp
@section('page_body')
<p>Total allocated LKR {{ number_format((float)$run->total_allocated,2) }}</p>
<table class="table table-sm"><thead><tr><th>Job</th><th>Base</th><th>Allocated</th></tr></thead><tbody>
@foreach($lines as $line)<tr><td>{{ $line['job_number'] ?? $line['job_id'] }}</td><td>{{ $line['base_quantity'] }}</td><td>{{ $line['allocated_amount'] }}</td></tr>@endforeach
</tbody></table>
<form method="post" action="{{ url('accounting/manufacturing-overhead/runs/'.$run->id.'/post') }}">@csrf<button class="btn btn-primary">Post run</button></form>
@endsection
@extends('accounting::phase2._page')
@php $pageTitle = 'Overhead rules'; @endphp
@section('page_actions')<a href="{{ url('accounting/manufacturing-overhead/rules/create') }}" class="btn btn-primary btn-sm">New rule</a>@endsection
@section('page_body')
<table class="table table-sm"><thead><tr><th>Name</th><th>Base</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($rules as $r)
<tr><td>{{ $r->name }}</td><td>{{ $r->allocation_base }}</td><td>{{ $r->status }}</td>
<td>@if($r->status==='draft')<form method="post" action="{{ url('accounting/manufacturing-overhead/rules/'.$r->id.'/approve') }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0">Approve</button></form>@endif
@if($r->status==='approved')<form method="post" action="{{ url('accounting/manufacturing-overhead/rules/'.$r->id.'/activate') }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0">Activate</button></form>@endif
</td></tr>
@endforeach
</tbody></table>
@endsection
@extends('accounting::phase2._page')
@php $pageTitle = 'Overhead allocation'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/manufacturing-overhead/allocation/preview') }}" class="row g-3">@csrf
    <div class="col-md-6"><select name="rule_id" class="form-select">@foreach($rules as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><input type="date" name="period_from" class="form-control" value="{{ date('Y-m-01') }}"></div>
    <div class="col-md-3"><input type="date" name="period_to" class="form-control" value="{{ date('Y-m-d') }}"></div>
    <div class="col-12"><button class="btn btn-primary">Preview run</button></div>
</form>
@endsection
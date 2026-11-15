@extends('accounting::phase2._page')
@section('page_body')
<form method="POST" action="{{ url('accounting/phase4/year-end/years') }}" class="row g-2 mb-3">@csrf
    <div class="col-md-3"><input name="name" class="form-control" placeholder="FY label" required></div>
    <div class="col-md-2"><input name="starts_on" type="date" class="form-control" required></div>
    <div class="col-md-2"><input name="ends_on" type="date" class="form-control" required></div>
    <div class="col-auto"><button class="btn btn-primary">Create FY</button></div>
</form>
<ul>@foreach($years as $y)<li><a href="{{ url('accounting/phase4/year-end/'.$y->id) }}">{{ $y->name }}</a> ({{ $y->status }})</li>@endforeach</ul>
@endsection
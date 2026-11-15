@extends('accounting::phase2._page')
@section('page_body')
<form method="POST" action="{{ url('accounting/phase4/custom-reports') }}" class="row g-2 mb-4">@csrf
    <div class="col-md-4"><input name="name" class="form-control" placeholder="Report name" required></div>
    <div class="col-md-4"><select name="report_type" class="form-select">@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary">Save</button></div>
</form>
<ul>@foreach($reports as $r)<li>{{ $r->name }} ({{ $r->report_type }})</li>@endforeach</ul>
@endsection
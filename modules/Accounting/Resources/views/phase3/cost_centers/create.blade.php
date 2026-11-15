@extends('accounting::phase2._page')
@php $pageTitle = 'New Cost Center'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/cost-centers') }}" class="row g-3">@csrf
    <div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="Code" required></div>
    <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
    <div class="col-md-4"><input name="description" class="form-control form-control-sm" placeholder="Description"></div>
    <div class="col-md-2">
        <select name="parent_id" class="form-select form-select-sm"><option value="">No parent</option>
        @foreach($parents as $p)<option value="{{ $p->id }}">{{ $p->code }}</option>@endforeach
        </select>
    </div>
    <div class="col-12"><button class="btn btn-primary btn-sm">Create</button></div>
</form>
@endsection
@extends('accounting::phase2._page')
@php $pageTitle = 'Edit '.$center->code; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/cost-centers/'.$center->id) }}" class="row g-3">@csrf
    <div class="col-md-2"><input name="code" value="{{ $center->code }}" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><input name="name" value="{{ $center->name }}" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><input name="description" value="{{ $center->description }}" class="form-control form-control-sm"></div>
    <div class="col-md-2">
        <select name="parent_id" class="form-select form-select-sm"><option value="">No parent</option>
        @foreach($parents as $p)<option value="{{ $p->id }}" @selected($center->parent_id==$p->id)>{{ $p->code }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="active" @selected($center->status==='active')>active</option>
            <option value="inactive" @selected($center->status==='inactive')>inactive</option>
        </select>
    </div>
    <div class="col-12"><button class="btn btn-primary btn-sm">Update</button></div>
</form>
@endsection
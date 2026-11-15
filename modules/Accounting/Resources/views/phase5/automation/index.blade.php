@extends('accounting::phase2._page')
@section('page_body')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="post" action="{{ url('accounting/phase5/automation') }}" class="border rounded p-3 mb-3">@csrf
    <div class="row g-2">
        <div class="col-md-3"><input name="name" class="form-control" placeholder="Rule name" required></div>
        <div class="col-md-3">
            <select name="trigger" class="form-select" required>
                @foreach($triggers as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="action" class="form-select" required>
                @foreach($actions as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
    </div>
</form>
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Name</th><th>Trigger</th><th>Action</th><th>Active</th><th></th></tr></thead>
    <tbody>
    @foreach($rules as $r)
        <tr>
            <td>{{ $r->id }}</td><td>{{ $r->name }}</td><td>{{ $r->trigger }}</td><td>{{ $r->action }}</td>
            <td>{{ $r->active ? 'yes' : 'no' }}</td>
            <td>
                <form method="post" action="{{ url('accounting/phase5/automation/'.$r->id.'/toggle') }}">@csrf
                    <input type="hidden" name="active" value="{{ $r->active ? 0 : 1 }}">
                    <button class="btn btn-sm btn-outline-secondary">{{ $r->active ? 'Pause' : 'Resume' }}</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection

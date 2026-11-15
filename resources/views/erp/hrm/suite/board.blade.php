@extends('erp.layout')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0">{{ $title }}</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#hr-add-form" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">Add</button>
        @foreach($links ?? [] as [$label, $url])
            <a href="{{ $url }}" class="btn btn-outline-secondary btn-sm">{{ $label }}</a>
        @endforeach
    </div>
</div>
<p class="text-muted small">{{ $intro }}</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="collapse {{ $errors->any() ? 'show' : '' }} mb-3" id="hr-add-form">
    <div class="card border-0 shadow-sm" style="max-width: 720px;">
        <div class="card-header"><h6 class="mb-0">Add</h6></div>
        <div class="card-body">
            <form method="POST" action="{{ $store }}">
                @csrf
                <div class="row g-3">
                    @foreach($fields as $field)
                        <div class="col-md-6">
                            <label class="form-label">{{ $field['label'] }}</label>
                            @if(in_array($field['type'], ['employee', 'line', 'select'], true))
                                <select name="{{ $field['name'] }}" class="form-select">
                                    <option value="">—</option>
                                    @foreach($field['options'] ?? [] as $value => $label)
                                        <option value="{{ $value }}" {{ (string) old($field['name']) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif($field['type'] === 'textarea')
                                <textarea name="{{ $field['name'] }}" class="form-control" rows="2">{{ old($field['name']) }}</textarea>
                            @else
                                <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text') }}" name="{{ $field['name'] }}" class="form-control" value="{{ old($field['name'], $field['type'] === 'date' ? date('Y-m-d') : '') }}" @if($field['type'] === 'number') step="0.01" min="0" @endif>
                            @endif
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-primary mt-3">Save</button>
            </form>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr>@foreach($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($records as $record)
                    <tr>@foreach($record as $cell)<td>@if(is_string($cell) && str_starts_with(ltrim($cell), '<')){!! $cell !!}@else{{ $cell }}@endif</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="text-center text-muted py-4">Nothing saved yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($records, 'links'))
        <div class="card-footer bg-white">{{ $records->links() }}</div>
    @endif
</div>
@if(!empty($extraColumns))
<div class="card border-0 shadow-sm">
    <div class="card-header"><h6 class="mb-0">{{ $extraTitle }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr>@foreach($extraColumns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($extraRecords as $record)
                    <tr>@foreach($record as $cell)<td>@if(is_string($cell) && str_starts_with(ltrim($cell), '<')){!! $cell !!}@else{{ $cell }}@endif</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($extraColumns) }}" class="text-muted text-center py-3">None yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($extraRecords) && method_exists($extraRecords, 'links'))
        <div class="card-footer bg-white">{{ $extraRecords->links() }}</div>
    @endif
</div>
@endif
@endsection

@extends('erp.layout')

@section('title', 'Add Sales Route')

@section('content')
<div class="container-fluid py-3">
    <h1 class="h4 mb-3">Add Sales Route</h1>
    <div class="card shadow-sm border-0" style="max-width:720px">
        <div class="card-body">
            <form method="POST" action="{{ route('sales-routes.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Route name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. Colombo North">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. R-COL">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', '1') == 1)>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                @include('erp.sales_routes.partials.assign-sales-persons', ['salesPersons' => $salesPersons, 'selectedIds' => old('sales_person_ids', [])])
                <button class="btn btn-primary" type="submit">Save Route</button>
                <a href="{{ route('sales-routes.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

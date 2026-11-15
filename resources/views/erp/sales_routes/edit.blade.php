@extends('erp.layout')

@section('title', 'Edit Sales Route')

@section('content')
<div class="container-fluid py-3">
    <h1 class="h4 mb-3">Edit Route — {{ $salesRoute->name }}</h1>
    <div class="card shadow-sm border-0" style="max-width:720px">
        <div class="card-body">
            @canRoute('sales-routes.update')
<form method="POST" action="{{ route('sales-routes.update', $salesRoute) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Route name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $salesRoute->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $salesRoute->code) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $salesRoute->description) }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $salesRoute->is_active ? '1' : '0') == 1)>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                @include('erp.sales_routes.partials.assign-sales-persons', [
                    'salesPersons' => $salesPersons,
                    'selectedIds' => old('sales_person_ids', $salesRoute->salesPersons->pluck('id')->all()),
                    'currentRouteId' => $salesRoute->id,
                ])
                <button class="btn btn-primary" type="submit">Update</button>
                <a href="{{ route('sales-routes.index') }}" class="btn btn-secondary">Back</a>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endsection

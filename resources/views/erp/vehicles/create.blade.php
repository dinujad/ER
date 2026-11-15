@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add Vehicle</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('vehicles.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Number Plate</label>
                        <input type="text" class="form-control @error('number_plate') is-invalid @enderror" name="number_plate" value="{{ old('number_plate') }}" required>
                        @error('number_plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-control" name="type" value="{{ old('type') }}" placeholder="e.g. Van, Lorry">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default driver <span class="text-muted small">(fallback)</span></label>
                        <select name="driver_id" class="form-select">
                            <option value="">— No driver —</option>
                            @foreach($drivers as $d)
                                <option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Vehicle</button>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

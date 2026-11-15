@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Vehicle</h1>
    <div class="card shadow mb-4">
        <div class="card-body">
            @canRoute('vehicles.update')
<form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $vehicle->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Number Plate</label>
                        <input type="text" class="form-control @error('number_plate') is-invalid @enderror" name="number_plate" value="{{ old('number_plate', $vehicle->number_plate) }}" required>
                        @error('number_plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <input type="text" class="form-control" name="type" value="{{ old('type', $vehicle->type) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default driver <span class="text-muted small">(fallback)</span></label>
                        <select name="driver_id" class="form-select">
                            <option value="">— No default —</option>
                            @foreach($drivers as $d)
                                <option value="{{ $d->id }}" {{ old('driver_id', $vehicle->driver_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">දිනයට assign කර නැත්නම් මෙය use වෙනවා. දිනයට driver දාන්න යටින්.</small>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Vehicle</button>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
@endcanRoute
        </div>
    </div>

    @include('erp.vehicles.partials.daily-assignments', compact('vehicle', 'drivers', 'assignments'))
</div>
@endsection

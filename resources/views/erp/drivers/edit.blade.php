@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Driver</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            @canRoute('drivers.update')
<form method="POST" action="{{ route('drivers.update', $driver) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $driver->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $driver->phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License No</label>
                        <input type="text" class="form-control" name="license_no" value="{{ old('license_no', $driver->license_no) }}">
                    </div>
                    <div class="col-12"><hr class="my-3"><strong>Dashboard login</strong></div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $driver->user?->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New password (leave blank to keep)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Driver</button>
                    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
@endcanRoute
        </div>
    </div>
</div>
@endsection

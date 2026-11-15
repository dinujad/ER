@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add Driver</h1>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('drivers.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License No</label>
                        <input type="text" class="form-control" name="license_no" value="{{ old('license_no') }}">
                    </div>
                    <div class="col-12"><hr><strong>Dashboard login (optional)</strong></div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Min 6 characters">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Driver</button>
                    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

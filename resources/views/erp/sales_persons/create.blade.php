@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add Sales Person</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">New Sales Person Details</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('sales-persons.store') }}">
                @csrf
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Phone (SMS) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="07XXXXXXXX" required>
                    <small class="text-muted d-block">Login credentials will be sent to this number via SMS (Text.lk).</small>
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="bank_account_number">Bank Account Number (Optional)</label>
                    <input type="text" class="form-control @error('bank_account_number') is-invalid @enderror" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}">
                    @error('bank_account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="sales_route_id">Sales Route</label>
                    <select name="sales_route_id" id="sales_route_id" class="form-control @error('sales_route_id') is-invalid @enderror">
                        <option value="">— No route —</option>
                        @foreach($salesRoutes as $route)
                            <option value="{{ $route->id }}" @selected((string) old('sales_route_id') === (string) $route->id)>
                                {{ $route->name }}@if($route->code) ({{ $route->code }})@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block">Same-route sales persons share customer visibility.</small>
                    @error('sales_route_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Sales Person</button>
                <a href="{{ route('sales-persons.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

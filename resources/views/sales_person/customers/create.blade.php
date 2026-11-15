@extends('sales_person.layout')

@section('content')
<div class="container-fluid">
    <style>
        .customer-create-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .customer-create-header {
            background: linear-gradient(135deg, #1e3a5f, #2d5a87);
            color: #fff;
            padding: 1rem 1.25rem;
        }
        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0a58ca;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 0.45rem;
            margin-bottom: 1rem;
        }
        .field-hint {
            font-size: 0.78rem;
            color: #6c757d;
        }
    </style>

    <h1 class="h3 mb-3 text-gray-800">{{ isset($customer) ? 'Edit Customer' : 'Add New Customer' }}</h1>

    <div class="card customer-create-card mb-4">
        <div class="customer-create-header">
            <h6 class="m-0 fw-bold">{{ isset($customer) ? 'Edit business' : 'Customer Registration' }}</h6>
            <small class="opacity-75">Save straight away. No SMS OTP.</small>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ isset($customer) ? route('sales-person.customers.update', $customer) : route('sales-person.customers.store') }}" enctype="multipart/form-data">
                @csrf
                @if(isset($customer))
                    @method('PUT')
                @endif

                <div class="section-title">Personal Details</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $customer->email ?? '') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="07XXXXXXXX" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $customer->address ?? '') }}" placeholder="Customer address">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="section-title mt-2">Business Details <span class="text-danger">*</span></div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('business_name') is-invalid @enderror" id="business_name" name="business_name" value="{{ old('business_name', $customer->business_name ?? '') }}" placeholder="e.g. ABC Textile" required>
                        <div class="field-hint">Invoices and orders show this business name as primary.</div>
                        @error('business_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="br_number" class="form-label">BR Number</label>
                        <input type="text" class="form-control @error('br_number') is-invalid @enderror" id="br_number" name="br_number" value="{{ old('br_number', $customer->br_number ?? '') }}" placeholder="Business registration no">
                        @error('br_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="tin" class="form-label">TIN Number</label>
                        <input type="text" class="form-control @error('tin') is-invalid @enderror" id="tin" name="tin" value="{{ old('tin', $customer->tin ?? '') }}" placeholder="9-digit TIN" maxlength="9" inputmode="numeric" pattern="\d{9}">
                        <div class="field-hint">Purchaser TIN on tax invoice. 9 digits.</div>
                        @error('tin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="br_document" class="form-label">BR Document (Photo/PDF)</label>
                        <input type="file" class="form-control @error('br_document') is-invalid @enderror" id="br_document" name="br_document" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                        <div class="field-hint">Optional. Upload business registration image or PDF (max 5MB). @if(!empty($customer?->br_document_path)) A document is already saved. Upload only to replace it. @endif</div>
                        @error('br_document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="business_address" class="form-label">Business Address</label>
                    <textarea class="form-control @error('business_address') is-invalid @enderror" id="business_address" name="business_address" rows="2" placeholder="Business location / billing address">{{ old('business_address', $customer->business_address ?? '') }}</textarea>
                    @error('business_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary px-4">{{ isset($customer) ? 'Update Customer' : 'Save Customer' }}</button>
                <a href="{{ route('sales-person.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

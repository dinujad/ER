@extends('erp.layout')

@php
    $isEdit = isset($customer);
    $defaultType = $defaultType ?? ($customer->customer_type ?? 'wholesale');
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">{{ $isEdit ? 'Edit Customer' : 'Add Customer' }}</h1>
        <a href="{{ $isEdit && ($customer->customer_type ?? '') === 'wholesale' ? route('erp.customers.show', $customer) : route('erp.customers.index', ['tab' => $defaultType === 'retail' ? 'retail' : 'wholesale']) }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <strong>{{ $isEdit ? 'Edit Customer (Admin)' : 'Customer Registration (Admin)' }}</strong>
            <div class="small opacity-75">Business name is required — invoices &amp; orders show business name as primary.</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('erp.customers.update', $customer) : route('erp.customers.store') }}" enctype="multipart/form-data">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <h6 class="text-primary border-bottom pb-2 mb-3">Customer type</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="customer_type" class="form-select" required>
                            <option value="wholesale" @selected(old('customer_type', $defaultType) === 'wholesale')>Wholesale</option>
                            <option value="retail" @selected(old('customer_type', $defaultType) === 'retail')>Retail</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assign to Sales Person</label>
                        <select name="sales_person_id" class="form-select">
                            <option value="">— Admin only (no sales person) —</option>
                            @foreach($salesPersons as $sp)
                                <option value="{{ $sp->id }}" @selected((string) old('sales_person_id', $customer->added_by ?? '') === (string) $sp->id)>{{ $sp->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Optional. Leave empty if admin owns the customer.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Credit Limit (Rs)</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
                    </div>
                </div>

                <h6 class="text-primary border-bottom pb-2 mb-3">Business details <span class="text-danger">*</span></h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Business Name <span class="text-danger">*</span></label>
                        <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $customer->business_name ?? '') }}" required placeholder="e.g. ABC Textile">
                        <small class="text-muted">This name appears on bills / invoices / order lists.</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">BR Number</label>
                        <input type="text" name="br_number" class="form-control" value="{{ old('br_number', $customer->br_number ?? '') }}" placeholder="Business registration no">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">TIN Number</label>
                        <input type="text" name="tin" class="form-control" value="{{ old('tin', $customer->tin ?? '') }}" placeholder="9-digit TIN" maxlength="9" inputmode="numeric" pattern="\d{9}">
                        <small class="text-muted">Purchaser TIN on tax invoice. 9 digits.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Business Address</label>
                        <textarea name="business_address" class="form-control" rows="2" placeholder="Billing / business location">{{ old('business_address', $customer->business_address ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">BR Document (Photo/PDF)</label>
                        <input type="file" name="br_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                        <small class="text-muted">Optional. Max 5MB.@if(!empty($customer?->br_document_path)) A document is already saved — upload only to replace.@endif</small>
                    </div>
                </div>

                <h6 class="text-primary border-bottom pb-2 mb-3 mt-2">Contact person</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
                        <small class="text-muted">Person name (secondary — business name is primary on bills).</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="07XXXXXXXX" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Personal Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address ?? '') }}">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary px-4">
                        @if($isEdit)
                            Update Customer
                        @else
                            <i class="bi bi-person-plus me-1"></i> Save Customer
                        @endif
                    </button>
                    @unless($isEdit)
                        <a href="{{ route('orders.create') }}" class="btn btn-outline-success">Create Order instead</a>
                    @endunless
                    <a href="{{ $isEdit && ($customer->customer_type ?? '') === 'wholesale' ? route('erp.customers.show', $customer) : route('erp.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

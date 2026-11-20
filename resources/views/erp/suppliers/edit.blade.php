@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Edit Supplier</h4>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        @canRoute('suppliers.update')
<form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $supplier->company_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $supplier->phone) }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax / VAT No</label>
                    <input type="text" name="tax_vat_no" class="form-control" value="{{ old('tax_vat_no', $supplier->tax_vat_no) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Payment terms (days)</label>
                    <input type="number" name="payment_terms_days" class="form-control" min="0" max="365" value="{{ old('payment_terms_days', $supplier->payment_terms_days) }}" placeholder="{{ config('dermas.default_supplier_terms_days', 30) }}">
                    <small class="text-muted">Bill due date = bill date + these days. Empty = company default.</small>
                </div>
                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2 mb-2">Bank details <small class="text-muted fw-normal">(for supplier payment runs / bank files)</small></h6></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank account name</label>
                    <input type="text" name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $supplier->bank_account_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank account no.</label>
                    <input type="text" name="bank_account_no" class="form-control" value="{{ old('bank_account_no', $supplier->bank_account_no) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank</label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $supplier->bank_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank code (SLIPS)</label>
                    <input type="text" name="bank_code" class="form-control" value="{{ old('bank_code', $supplier->bank_code) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Branch code</label>
                    <input type="text" name="branch_code" class="form-control" value="{{ old('branch_code', $supplier->branch_code) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opening Balance</label>
                    <input type="number" name="opening_balance" class="form-control" step="0.01" value="{{ old('opening_balance', $supplier->opening_balance) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $supplier->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $supplier->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier->notes) }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">View</a>
                </div>
            </div>
        </form>
@endcanRoute
    </div>
</div>
@endsection

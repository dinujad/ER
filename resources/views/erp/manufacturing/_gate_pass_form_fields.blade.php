@php
    $washingTypes = \App\Models\WashingTransfer::washingTypeOptions();
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Washing Type <span class="text-danger">*</span></label>
        <select name="washing_type" class="form-select" required>
            <option value="">Select washing type...</option>
            @foreach($washingTypes as $key => $label)
                <option value="{{ $key }}" @selected(old('washing_type') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Vehicle Number <span class="text-danger">*</span></label>
        <input type="text" name="vehicle_number" class="form-control" value="{{ old('vehicle_number') }}"
            placeholder="e.g. CAB-1234" required maxlength="40">
    </div>
    <div class="col-12">
        <label class="form-label">Washing Factory Name <span class="text-danger">*</span></label>
        <input type="text" name="washing_factory_name" class="form-control" value="{{ old('washing_factory_name') }}"
            placeholder="Name of external washing factory" required maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Admin Signature (Name) <span class="text-danger">*</span></label>
        <input type="text" name="admin_signatory" class="form-control" value="{{ old('admin_signatory', auth()->user()?->name) }}"
            placeholder="Authorized by" required maxlength="255">
        <small class="text-muted">Production / admin authority</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">Driver Signature (Name) <span class="text-danger">*</span></label>
        <input type="text" name="driver_signatory" class="form-control" value="{{ old('driver_signatory') }}"
            placeholder="Driver full name" required maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Washing Factory Signature (Name) <span class="text-danger">*</span></label>
        <input type="text" name="factory_signatory" class="form-control" value="{{ old('factory_signatory') }}"
            placeholder="Factory representative" required maxlength="255">
    </div>
    <div class="col-md-6">
        <label class="form-label">Queue Number <span class="text-muted">(optional)</span></label>
        <input type="text" name="queue_no" class="form-control" value="{{ old('queue_no') }}" placeholder="e.g. Q01" maxlength="50">
        <small class="text-muted">Leave blank to auto-generate.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Remarks <span class="text-muted">(optional)</span></label>
        <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" maxlength="1000" placeholder="Any notes for the gate pass">
    </div>
</div>

@php
    $emp = $employee ?? null;
    $employmentTypes = ['permanent' => 'Permanent', 'temporary' => 'Temporary', 'contract' => 'Contract', 'trainee' => 'Trainee'];
    $payTypes = ['monthly' => 'Monthly', 'daily' => 'Daily', 'hourly' => 'Hourly', 'piece_rate' => 'Piece rate'];
    $idTypes = ['nic' => 'NIC', 'passport' => 'Passport', 'driving_licence' => 'Driving licence'];
@endphp
<div class="alert alert-light border small mb-3">
    Excel import keeps the Hik-Connect ID only. Employee code, address, emergency contact, bank, pay type, and documents are added here. The Excel file format is not used for these fields.
</div>
<h6>Identity</h6>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Employee code</label>
        <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $emp->employee_code ?? '') }}" placeholder="Leave blank to assign EMP-00001" maxlength="32">
        <small class="text-muted">Separate from the Hikvision attendance ID.</small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">ID document type</label>
        <select name="id_type" class="form-select">
            <option value="">—</option>
            @foreach($idTypes as $value => $label)
                <option value="{{ $value }}" {{ old('id_type', $emp->id_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Address</label>
    <textarea name="address" class="form-control" rows="2">{{ old('address', $emp->address ?? '') }}</textarea>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Emergency contact name</label>
        <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $emp->emergency_contact_name ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Emergency contact phone</label>
        <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $emp->emergency_contact_phone ?? '') }}">
    </div>
</div>
<h6>Job</h6>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
            <option value="">—</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" {{ (string) old('department_id', $emp->department_id ?? '') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Designation</label>
        <select name="designation_id" class="form-select">
            <option value="">—</option>
            @foreach($designations as $designation)
                <option value="{{ $designation->id }}" {{ (string) old('designation_id', $emp->designation_id ?? '') === (string) $designation->id ? 'selected' : '' }}>{{ $designation->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Production line</label>
        <select name="production_line_id" class="form-select">
            <option value="">—</option>
            @foreach($lines as $line)
                <option value="{{ $line->id }}" {{ (string) old('production_line_id', $emp->production_line_id ?? '') === (string) $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Reporting manager</label>
        <select name="reporting_manager_id" class="form-select">
            <option value="">—</option>
            @foreach($managers as $manager)
                <option value="{{ $manager->id }}" {{ (string) old('reporting_manager_id', $emp->reporting_manager_id ?? '') === (string) $manager->id ? 'selected' : '' }}>
                    {{ $manager->display_name ?: $manager->name }}@if($manager->employee_code) · {{ $manager->employee_code }}@endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Employment type</label>
        <select name="employment_type" class="form-select">
            @foreach($employmentTypes as $value => $label)
                <option value="{{ $value }}" {{ old('employment_type', $emp->employment_type ?? 'permanent') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Default shift</label>
        <select name="shift_id" class="form-select">
            <option value="">No shift</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}" {{ (string) old('shift_id', $emp->shift_id ?? '') === (string) $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<h6>Pay and bank</h6>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Pay method</label>
        <select name="pay_type" class="form-select">
            @foreach($payTypes as $value => $label)
                <option value="{{ $value }}" {{ old('pay_type', $emp->pay_type ?? 'monthly') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Rate (Rs)</label>
        <input type="number" name="pay_rate" class="form-control" min="0" step="0.01" value="{{ old('pay_rate', $emp->pay_rate ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Payroll registration no.</label>
        <input type="text" name="payroll_registration_no" class="form-control" value="{{ old('payroll_registration_no', $emp->payroll_registration_no ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Bank</label>
        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $emp->bank_name ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Branch</label>
        <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch', $emp->bank_branch ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Account name</label>
        <input type="text" name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $emp->bank_account_name ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Account number</label>
        <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $emp->bank_account_number ?? '') }}">
    </div>
</div>

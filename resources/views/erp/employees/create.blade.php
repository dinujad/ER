@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Employee</h1>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0">Employee details</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">First name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Filled from first and last name if left blank">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hik-Connect person ID</label>
                        <input type="text" name="hikvision_person_id" class="form-control font-monospace" value="{{ old('hikvision_person_id') }}" placeholder="Same ID column as the Excel sheet">
                        <small class="text-muted">Text, so leading zeros stay. This is the attendance ID, not the employee code.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">—</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Required for supervisors">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIC / ID <span class="text-danger">*</span></label>
                        <input type="text" name="nic" class="form-control" value="{{ old('nic') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact" class="form-control" value="{{ old('contact') }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Birthday <span class="text-danger">*</span></label>
                        <input type="date" name="birthday" class="form-control" value="{{ old('birthday') }}" max="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Join Date <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="form-control" value="{{ old('join_date', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>{{ $role->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Photo <span class="text-muted">(Optional)</span></label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Not required. You can add or change later.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hik-Connect valid from</label>
                        <input type="datetime-local" name="hikvision_valid_from" class="form-control" value="{{ old('hikvision_valid_from') }}">
                        <small class="text-muted">Device access period only. Not the join date.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hik-Connect valid to</label>
                        <input type="datetime-local" name="hikvision_valid_to" class="form-control" value="{{ old('hikvision_valid_to') }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <hr>
                @include('erp.employees._hr_fields')
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

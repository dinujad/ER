@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Employee</h1>
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
            @canRoute('employees.update')
<form method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">First name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">—</option>
                            <option value="male" {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}" placeholder="Required for Supervisors">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIC / ID <span class="text-danger">*</span></label>
                        <input type="text" name="nic" class="form-control" value="{{ old('nic', $employee->nic) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hik-Connect person ID</label>
                        <input type="text" name="hikvision_person_id" class="form-control font-monospace" value="{{ old('hikvision_person_id', $employee->hikvision_person_id) }}" placeholder="Excel ID from Hik-Connect">
                        <small class="text-muted">Stored as text (keeps leading zeros). Used to match attendance — not the ERP employee number.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">EMP enroll ID <span class="text-muted">(legacy attendance)</span></label>
                        <input type="text" name="device_enroll_id" class="form-control" value="{{ old('device_enroll_id', $employee->device_enroll_id) }}" placeholder="e.g. 9, 6, 40">
                        <small class="text-muted">Fallback if Hik-Connect ID is empty. Do not use as the database primary key.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact" class="form-control" value="{{ old('contact', $employee->contact) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birthday" class="form-control" value="{{ old('birthday', $employee->birthday ? \Carbon\Carbon::parse($employee->birthday)->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}">
                        <small class="text-muted">Set birthday for employees added earlier without it.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hik-Connect valid from</label>
                        <input type="datetime-local" name="hikvision_valid_from" class="form-control" value="{{ old('hikvision_valid_from', $employee->hikvision_valid_from?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hik-Connect valid to</label>
                        <input type="datetime-local" name="hikvision_valid_to" class="form-control" value="{{ old('hikvision_valid_to', $employee->hikvision_valid_to?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $employee->notes) }}</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Join Date <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="form-control" value="{{ old('join_date', $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->format('Y-m-d') : '') }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            @foreach($roles as $key => $label)
                                <option value="{{ $key }}" {{ old('role', $employee->role) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Photo <span class="text-muted">(Optional)</span></label>
                        @if($employee->photo)
                            <div class="mb-2"><img src="{{ asset('storage/' . $employee->photo) }}" alt="Photo" class="rounded" style="max-width: 80px; max-height: 80px; object-fit: cover;"></div>
                        @endif
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current photo.</small>
                    </div>
                </div>
                <hr>
                @include('erp.employees._hr_fields', ['employee' => $employee])
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save profile</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
@endcanRoute
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0">Documents</h6></div>
                <div class="card-body">
                    <form action="{{ route('employees.documents.store', $employee->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small">Title</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="NIC copy, contract" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">File</label>
                            <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Add</button></div>
                    </form>
                    <ul class="list-group list-group-flush">
                        @forelse($employee->documents as $doc)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">{{ $doc->title }}</a>
                                @canRoute('employees.documents.destroy')
<form action="{{ route('employees.documents.destroy', [$employee->id, $doc->id]) }}" method="POST" onsubmit="return confirm('Remove this document?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
@endcanRoute
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted">No documents yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0">Transfer, promotion, salary history</h6></div>
                <div class="card-body">
                    <form action="{{ route('employees.changes.store', $employee->id) }}" method="POST" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-4">
                            <select name="change_type" class="form-select form-select-sm" required>
                                <option value="transfer">Transfer</option>
                                <option value="promotion">Promotion</option>
                                <option value="salary">Salary change</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4"><input type="date" name="effective_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col-12"><input type="text" name="summary" class="form-control form-control-sm" placeholder="What changed" required></div>
                        <div class="col-12"><button class="btn btn-sm btn-outline-primary">Add history</button></div>
                    </form>
                    <ul class="list-unstyled small mb-0">
                        @forelse($employee->changes as $change)
                            <li class="border-bottom py-2">
                                <strong>{{ ucfirst($change->change_type) }}</strong> · {{ $change->effective_date->format('d M Y') }}<br>
                                {{ $change->summary }}
                            </li>
                        @empty
                            <li class="text-muted">No history yet. Saving a department, designation, line, manager, or pay change records one automatically.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

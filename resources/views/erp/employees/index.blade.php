@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Employees</h6>
                        <div class="d-flex gap-2 me-3">
                            @if(auth()->user()?->can_access('employees.create'))
                            <a href="{{ route('employees.hik-connect-import.create') }}" class="btn btn-light btn-sm"><i class="bi bi-fingerprint me-1"></i> Import Hik-Connect Employees</a>
                            <a href="{{ route('employees.import.create') }}" class="btn btn-light btn-sm"><i class="bi bi-upload me-1"></i> Import CSV / Excel</a>
                            <a href="{{ route('employees.create') }}" class="btn btn-light btn-sm"><i class="bi bi-person-plus me-1"></i> Add Employee</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    {{-- Role filter + bulk actions above table --}}
                    <div class="d-flex flex-wrap align-items-center gap-3 px-4 pb-3">
                        <form method="GET" action="{{ route('employees.index') }}" class="d-flex align-items-center gap-2">
                            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, NIC, Hik ID" style="width: 180px;">
                            <label for="role" class="form-label mb-0 text-sm">Role</label>
                            <select name="role" id="role" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All</option>
                                @foreach($roles ?? [] as $r)
                                    <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ $r->label }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Any status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                        </form>
                        @canRoute('employees.bulk-delete')
<form id="bulk-delete-form" action="{{ route('employees.bulk-delete') }}" method="POST" class="d-none">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger" id="bulk-delete-btn"><i class="bi bi-trash me-1"></i> Delete selected</button>
                        </form>
@endcanRoute
                        <span class="text-muted small" id="selected-count"></span>
                    </div>
                    <div class="table-responsive p-0">
                        <form id="table-form">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 40px;">
                                            <input type="checkbox" id="select-all" class="form-check-input" title="Select all">
                                        </th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Code</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Hik ID</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Role</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Mobile</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Joined</th>
                                        <th class="text-secondary opacity-7">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employees as $emp)
                                    <tr class="employee-row" data-id="{{ $emp->id }}">
                                        <td class="px-4 align-middle">
                                            <input type="checkbox" name="ids[]" value="{{ $emp->id }}" class="form-check-input row-check">
                                        </td>
                                        <td class="px-4">
                                            <span class="text-xs font-weight-bold">{{ $emp->employee_code ?: '—' }}</span>
                                        </td>
                                        <td class="px-4">
                                            <span class="text-xs font-weight-bold font-monospace">{{ $emp->hikvision_person_id ?: '—' }}</span>
                                        </td>
                                        <td class="px-4">
                                            <a href="{{ route('employees.show', $emp->id) }}" class="mb-0 text-sm fw-semibold text-decoration-none">{{ $emp->display_name ?: $emp->name }}</a>
                                            <p class="text-xs text-secondary mb-0">{{ $emp->nic }}@if($emp->department) · {{ $emp->department->name }}@endif @if($emp->defaultShift) · {{ $emp->defaultShift->name }}@endif</p>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0 text-uppercase">{{ str_replace('_', ' ', $emp->role) }}</p>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">{{ $emp->contact }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">{{ $emp->join_date ? \Carbon\Carbon::parse($emp->join_date)->format('d M Y') : '—' }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge {{ ($emp->status ?? 'active') === 'inactive' ? 'bg-secondary' : 'bg-success' }}">{{ ($emp->status ?? 'active') === 'inactive' ? 'Inactive' : 'Active' }}</span>
                                            <a href="{{ route('employees.show', $emp->id) }}" class="btn btn-link text-info p-0 mb-0 me-2" title="View profile"><i class="bi bi-person-badge"></i></a>
                                            @canRoute('employees.edit')
<a href="{{ route('employees.edit', $emp->id) }}" class="btn btn-link text-dark p-0 mb-0 me-2" title="Edit"><i class="bi bi-pencil"></i></a>
@endcanRoute
                                            @canRoute('employees.status')
<form action="{{ route('employees.status', $emp->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ ($emp->status ?? 'active') === 'inactive' ? 'active' : 'inactive' }}">
                                                <button type="submit" class="btn btn-link text-warning p-0 mb-0 me-2" title="{{ ($emp->status ?? 'active') === 'inactive' ? 'Activate' : 'Deactivate' }}"><i class="bi bi-person-dash"></i></button>
                                            </form>
@endcanRoute
                                            @canRoute('employees.destroy')
<form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger text-gradient p-0 mb-0" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
@endcanRoute
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<script>
(function () {
    var selectAll = document.getElementById('select-all');
    var rowChecks = document.querySelectorAll('.row-check');
    var rows = document.querySelectorAll('.employee-row');
    var bulkForm = document.getElementById('bulk-delete-form');
    var bulkBtn = document.getElementById('bulk-delete-btn');
    var countEl = document.getElementById('selected-count');
    var highlightClass = 'table-primary';
    if (!bulkForm) return;

    function updateUI() {
        var checked = document.querySelectorAll('.row-check:checked');
        var n = checked.length;
        rows.forEach(function (row) {
            var id = row.getAttribute('data-id');
            var cb = row.querySelector('.row-check');
            if (cb && cb.checked) {
                row.classList.add(highlightClass);
            } else {
                row.classList.remove(highlightClass);
            }
        });
        if (selectAll) selectAll.checked = n > 0 && n === rowChecks.length;
        if (n > 0) {
            bulkForm.classList.remove('d-none');
            if (bulkBtn) bulkBtn.disabled = false;
            countEl.textContent = n + ' selected';
        } else {
            bulkForm.classList.add('d-none');
            if (bulkBtn) bulkBtn.disabled = false;
            countEl.textContent = '';
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowChecks.forEach(function (c) { c.checked = selectAll.checked; });
            updateUI();
        });
    }
    rowChecks.forEach(function (c) {
        c.addEventListener('change', updateUI);
    });

    bulkForm.addEventListener('submit', function (e) {
        var ids = document.querySelectorAll('.row-check:checked');
        if (ids.length === 0) {
            e.preventDefault();
            return;
        }
        if (!confirm('Delete ' + ids.length + ' selected employee(s)?')) {
            e.preventDefault();
            return;
        }
        var arr = Array.from(ids).map(function(c) { return c.value; });
        arr.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            bulkForm.appendChild(input);
        });
    });

    updateUI();
})();
</script>
@endsection

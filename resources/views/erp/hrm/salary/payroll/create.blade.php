@extends('erp.layout')

@section('content')
<div class="mb-3">
    <a href="{{ route('payroll-runs.index') }}" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left me-1"></i> Payroll Runs</a>
</div>
<h4 class="mb-1">Add Payroll</h4>
<p class="text-muted small mb-4">Select month, year and <strong>one employee</strong>. After Proceed you can add more employees to the same run from the payroll page.</p>

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span>{{ session('error') }}</span>
        @if(session('existing_payroll_run_id'))
            <a href="{{ route('payroll-runs.show', session('existing_payroll_run_id')) }}" class="btn btn-sm btn-outline-danger">View existing payroll</a>
        @endif
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('payroll-runs.store') }}" id="payroll-create-form">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Month / Year <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <select name="period_month" class="form-select" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ old('period_month', now()->month) == $m ? 'selected' : '' }}>{{ now()->month($m)->format('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="number" name="period_year" class="form-control" value="{{ old('period_year', now()->year) }}" min="2020" max="2100" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <input type="text" id="employee-search" class="form-control" placeholder="Search by name..." autocomplete="off">
                    <select name="employee_id" id="employee-select" class="form-select mt-2" required size="1">
                        <option value="">-- Select one employee --</option>
                        @foreach($employees as $e)
                            @php
                                $empLabel = $e->display_name;
                                if ($e->hikvision_person_id) { $empLabel .= ' · '.$e->hikvision_person_id; }
                                if ($e->department) { $empLabel .= ' · '.$e->department->name; }
                            @endphp
                            <option value="{{ $e->id }}" data-name="{{ strtolower($empLabel) }}" {{ old('employee_id') == $e->id ? 'selected' : '' }}>{{ $empLabel }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Search above, then pick one from the list.</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4" id="btn-proceed"><i class="bi bi-check2 me-1"></i> Proceed</button>
                <a href="{{ route('payroll-runs.index') }}" class="btn btn-outline-secondary">Close</a>
            </div>
        </form>
    </div>
</div>
<p class="text-muted small mt-3 mb-0">Present and absent days are filled from attendance for the selected month. Employees are the same HR employee list.</p>
@endsection

@section('scripts')
<script>
(function() {
    var searchInput = document.getElementById('employee-search');
    var selectEl = document.getElementById('employee-select');
    if (!selectEl) return;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var opts = selectEl.querySelectorAll('option[value]');
            opts.forEach(function(opt) {
                var name = (opt.getAttribute('data-name') || '').toLowerCase();
                opt.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
})();
</script>
@endsection

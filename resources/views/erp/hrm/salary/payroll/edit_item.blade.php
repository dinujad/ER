@extends('erp.layout')

@section('content')
<div class="mb-4">
    <a href="{{ route('payroll-runs.show', $payrollRun) }}" class="text-decoration-none">← {{ $payrollRun->title }}</a>
</div>
<h4 class="mb-2">Add Payroll</h4>
<p class="text-muted small mb-3">Payroll for {{ $payrollRun->period_start->format('F Y') }} · Location: All locations</p>

@php
    $details = $payroll_run_item->details ?? [];
    $locked = collect($details)->where('amount_type', 'system')->values();
    $earnings = collect($details)->where('type', \App\Models\SalaryComponent::TYPE_ALLOWANCE)->where('amount_type', '!=', 'system')->values()->all();
    $deductions = collect($details)->where('type', \App\Models\SalaryComponent::TYPE_DEDUCTION)->where('amount_type', '!=', 'system')->values()->all();
    if (empty($earnings)) { $earnings = [['name' => '', 'amount_type' => 'Fixed', 'value' => 0]]; }
    if (empty($deductions)) { $deductions = [['name' => '', 'amount_type' => 'Fixed', 'value' => 0]]; }
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        @if($locked->isNotEmpty())
            <p class="small text-muted">Locked from attendance, leave, loans, and production incentives: {{ $locked->map(fn ($line) => ($line['name'] ?? '').' Rs '.number_format((float) ($line['value'] ?? 0), 2))->join(' · ') }}. These are not edited here and are not added twice.</p>
        @endif
        @canRoute('payroll-runs.items.update')
<form method="POST" action="{{ route('payroll-runs.items.update', [$payrollRun, $payroll_run_item]) }}" id="payroll-item-form">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Payroll group name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" value="{{ $payrollRun->title }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" disabled>
                        <option>{{ ucfirst($payrollRun->status) }}</option>
                    </select>
                    <p class="small text-muted mb-0 mt-1">Payroll can not be deleted if status is final</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1 fw-bold">{{ $payroll_run_item->employee->name }}</p>
                    <p class="small text-muted mb-2">Work Duration: <strong>{{ number_format($payroll_run_item->work_duration, 2) }}</strong> {{ $payroll_run_item->work_duration_unit }}</p>
                    <p class="small text-muted mb-2">Overtime from shift: <strong>{{ number_format(($payroll_run_item->ot_minutes ?? 0) / 60, 1) }} h</strong> · Rs {{ number_format($payroll_run_item->ot_amount ?? 0, 2) }}. This stays in net pay when you save.</p>

                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label small mb-0">Days present</label>
                            <input type="number" name="days_present" class="form-control form-control-sm" value="{{ old('days_present', $payroll_run_item->days_present) }}" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small mb-0">Days absent</label>
                            <input type="number" name="days_absent" class="form-control form-control-sm" value="{{ old('days_absent', $payroll_run_item->days_absent) }}" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small mb-0">Leave days</label>
                            <input type="number" name="leave_days" class="form-control form-control-sm" step="0.5" value="{{ old('leave_days', $payroll_run_item->leave_days) }}" min="0" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Total work duration <span class="text-danger">*</span></label>
                        <input type="number" name="work_duration" id="work_duration" class="form-control" step="0.01" value="{{ old('work_duration', $payroll_run_item->work_duration) }}" min="0" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Duration Unit</label>
                        <select name="work_duration_unit" id="work_duration_unit" class="form-select">
                            <option value="Month" {{ old('work_duration_unit', $payroll_run_item->work_duration_unit) === 'Month' ? 'selected' : '' }}>Month</option>
                            <option value="Day" {{ old('work_duration_unit', $payroll_run_item->work_duration_unit) === 'Day' ? 'selected' : '' }}>Day</option>
                            <option value="Hour" {{ old('work_duration_unit', $payroll_run_item->work_duration_unit) === 'Hour' ? 'selected' : '' }}>Hour</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Amount per unit duration <span class="text-danger">*</span></label>
                        <input type="number" name="amount_per_unit" id="amount_per_unit" class="form-control" step="0.01" value="{{ old('amount_per_unit', $payroll_run_item->amount_per_unit) }}" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total</label>
                        <input type="text" id="basic_total_display" class="form-control bg-light" value="0.00" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <h6 class="mb-2">Earnings</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="earnings-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount Type</th>
                                    <th>Amount</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody id="earnings-tbody">
                                @foreach($earnings as $i => $e)
                                <tr class="earnings-row">
                                    <td><input type="text" name="earnings[{{ $i }}][description]" class="form-control form-control-sm" value="{{ old("earnings.{$i}.description", $e['name'] ?? '') }}" placeholder=""></td>
                                    <td>
                                        <select name="earnings[{{ $i }}][amount_type]" class="form-select form-select-sm">
                                            <option value="Fixed" {{ ($e['amount_type'] ?? 'Fixed') === 'Fixed' ? 'selected' : '' }}>Fixed</option>
                                            <option value="Percentage" {{ ($e['amount_type'] ?? '') === 'Percentage' ? 'selected' : '' }}>Percentage</option>
                                        </select>
                                    </td>
                                    <td><input type="number" name="earnings[{{ $i }}][amount]" class="form-control form-control-sm earnings-amount" step="0.01" value="{{ old("earnings.{$i}.amount", $e['value'] ?? 0) }}" min="0"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-earning" title="Add">+</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove">−</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small mb-0 mt-2"><strong>Total Rs <span id="earnings-total">0.00</span></strong></p>
                </div>

                <div class="col-md-4">
                    <h6 class="mb-2">Deductions</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="deductions-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount Type</th>
                                    <th>Amount</th>
                                    <th width="60"></th>
                                </tr>
                            </thead>
                            <tbody id="deductions-tbody">
                                @foreach($deductions as $i => $d)
                                <tr class="deductions-row">
                                    <td><input type="text" name="deductions[{{ $i }}][description]" class="form-control form-control-sm" value="{{ old("deductions.{$i}.description", $d['name'] ?? '') }}" placeholder=""></td>
                                    <td>
                                        <select name="deductions[{{ $i }}][amount_type]" class="form-select form-select-sm">
                                            <option value="Fixed" {{ ($d['amount_type'] ?? 'Fixed') === 'Fixed' ? 'selected' : '' }}>Fixed</option>
                                            <option value="Percentage" {{ ($d['amount_type'] ?? '') === 'Percentage' ? 'selected' : '' }}>Percentage</option>
                                        </select>
                                    </td>
                                    <td><input type="number" name="deductions[{{ $i }}][amount]" class="form-control form-control-sm deductions-amount" step="0.01" value="{{ old("deductions.{$i}.amount", $d['value'] ?? 0) }}" min="0"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-deduction" title="Add">+</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove">−</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small mb-0 mt-2"><strong>Total Rs <span id="deductions-total">0.00</span></strong></p>
                    <p class="mt-3 mb-0 fw-bold fs-5">Gross Amount Rs <span id="gross-amount">0.00</span></p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" rows="2">{{ old('note', $payroll_run_item->note) }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('payroll-runs.show', $payrollRun) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
@endcanRoute
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    function num(v) { return parseFloat(v) || 0; }
    function updateBasicTotal() {
        var duration = num(document.getElementById('work_duration').value);
        var amount = num(document.getElementById('amount_per_unit').value);
        var total = Math.round(duration * amount * 100) / 100;
        document.getElementById('basic_total_display').value = total.toFixed(2);
        updateGross();
    }
    function sumEarnings() {
        var sum = 0;
        document.querySelectorAll('.earnings-amount').forEach(function(inp) {
            sum += num(inp.value);
        });
        document.getElementById('earnings-total').textContent = sum.toFixed(2);
        return sum;
    }
    function sumDeductions() {
        var sum = 0;
        document.querySelectorAll('.deductions-amount').forEach(function(inp) {
            sum += num(inp.value);
        });
        document.getElementById('deductions-total').textContent = sum.toFixed(2);
        return sum;
    }
    function updateGross() {
        var basic = num(document.getElementById('basic_total_display').value);
        var earn = sumEarnings();
        var ded = sumDeductions();
        document.getElementById('gross-amount').textContent = (basic + earn - ded).toFixed(2);
    }
    document.getElementById('work_duration').addEventListener('input', updateBasicTotal);
    document.getElementById('amount_per_unit').addEventListener('input', updateBasicTotal);
    document.getElementById('payroll-item-form').addEventListener('input', function(e) {
        if (e.target.classList.contains('earnings-amount')) { sumEarnings(); updateGross(); }
        if (e.target.classList.contains('deductions-amount')) { sumDeductions(); updateGross(); }
    });
    document.getElementById('payroll-item-form').addEventListener('change', function(e) {
        if (e.target.classList.contains('earnings-amount') || e.target.classList.contains('deductions-amount')) { updateGross(); }
    });

    function addRow(tbodyId, namePrefix, isEarning) {
        var tbody = document.getElementById(tbodyId);
        var rows = tbody.querySelectorAll('tr');
        var idx = rows.length;
        var tr = document.createElement('tr');
        tr.className = isEarning ? 'earnings-row' : 'deductions-row';
        tr.innerHTML = '<td><input type="text" name="' + namePrefix + '[' + idx + '][description]" class="form-control form-control-sm"></td>' +
            '<td><select name="' + namePrefix + '[' + idx + '][amount_type]" class="form-select form-select-sm"><option value="Fixed">Fixed</option><option value="Percentage">Percentage</option></select></td>' +
            '<td><input type="number" name="' + namePrefix + '[' + idx + '][amount]" class="form-control form-control-sm ' + (isEarning ? 'earnings-amount' : 'deductions-amount') + '" step="0.01" value="0" min="0"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-primary ' + (isEarning ? 'btn-add-earning' : 'btn-add-deduction') + '" title="Add">+</button> <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove">−</button></td>';
        tbody.appendChild(tr);
        tr.querySelector('.earnings-amount, .deductions-amount').addEventListener('input', function() { sumEarnings(); sumDeductions(); updateGross(); });
        reindexNames(tbodyId, namePrefix, isEarning);
    }
    function reindexNames(tbodyId, namePrefix) {
        var tbody = document.getElementById(tbodyId);
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function(r, i) {
            var desc = r.querySelector('input[type="text"]');
            var sel = r.querySelector('select');
            var amt = r.querySelector('input[type="number"]');
            if (desc) desc.name = namePrefix + '[' + i + '][description]';
            if (sel) sel.name = namePrefix + '[' + i + '][amount_type]';
            if (amt) amt.name = namePrefix + '[' + i + '][amount]';
        });
    }
    function removeRow(btn) {
        var tbody = btn.closest('tbody');
        var rows = tbody.querySelectorAll('tr');
        if (rows.length <= 1) return;
        btn.closest('tr').remove();
        var namePrefix = tbody.id === 'earnings-tbody' ? 'earnings' : 'deductions';
        reindexNames(tbody.id, namePrefix);
        sumEarnings(); sumDeductions(); updateGross();
    }
    document.getElementById('payroll-item-form').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-add-earning')) { addRow('earnings-tbody', 'earnings', true); }
        if (e.target.classList.contains('btn-add-deduction')) { addRow('deductions-tbody', 'deductions', false); }
        if (e.target.classList.contains('btn-remove-row')) { removeRow(e.target); }
    });
    updateBasicTotal();
    sumEarnings();
    sumDeductions();
    updateGross();
})();
</script>
@endsection

@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Record Hourly Production Output</h6>
                </div>
                <div class="card-body">
                    @if(session('washing_print_url'))
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <span>Washing gate pass created successfully.</span>
                            <a href="{{ session('washing_print_url') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-file-pdf me-1"></i> Download Gate Pass PDF
                            </a>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row mb-3" id="hourlyFilters">
                            <div class="col-md-4">
                                <label class="form-label">Select Job</label>
                                <select id="jobSelect" class="form-select border px-2" required>
                                    <option value="">Choose Job...</option>
                                    @foreach($jobs as $j)
                                        <option value="{{ $j->id }}" {{ $selectedJobId == $j->id ? 'selected' : '' }}>
                                            {{ $j->job_number }} (Style: {{ $j->style_ref }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Line</label>
                                <select id="lineSelect" class="form-select border px-2" {{ $selectedJobId && $assignedLines->isNotEmpty() ? 'required' : 'disabled' }}>
                                    @if($assignedLines->isEmpty())
                                        <option value="">No lines assigned</option>
                                    @else
                                        @foreach($assignedLines as $line)
                                            <option value="{{ $line }}" {{ ($selectedLine ?? '') === $line ? 'selected' : '' }}>{{ $line }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @if($selectedJobId && $assignedLines->isNotEmpty())
                                    <small class="text-muted">Select line → enter hours → switch line for next.</small>
                                @elseif(!$selectedJobId)
                                    <small class="text-muted">Select a job first</small>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Production date</label>
                                <select id="productionDatePicker"
                                    class="form-select border px-2"
                                    {{ $selectedJobId && !empty($allowedDates) ? '' : 'disabled' }}
                                    required>
                                    @if($selectedJobId && !empty($allowedDates))
                                        @foreach($allowedDates as $d)
                                            <option value="{{ $d }}" @selected($selectedDate === $d)>
                                                {{ \Carbon\Carbon::parse($d)->format('d M Y') }}
                                                @if($d === ($today ?? date('Y-m-d'))) (Today) @endif
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">Select job first</option>
                                    @endif
                                </select>
                                @if($jobStartDate)
                                    <small class="text-muted">Job start: {{ \Carbon\Carbon::parse($jobStartDate)->format('d M Y') }} — today only (no future)</small>
                                @endif
                            </div>
                    </div>

                        @if($selectedJobId && $assignedLines->isNotEmpty() && !empty($lineStats))
                            @if($needsTargetSetup)
                                <div class="card border-warning border-2 mb-4">
                                    <div class="card-header bg-warning bg-opacity-25">
                                        <strong>Step 1 — දිනයේ target set කරන්න</strong>
                                        <span class="text-muted fw-normal">({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})</span>
                                    </div>
                                    <div class="card-body">
                                        <p class="small text-muted mb-3">
                                            අලුත් දවසක් — පළමුව line එකකට දිනයේ target එක confirm කරන්න. ඊට පස්සෙ hourly output දාන්න පුළුවන්.
                                        </p>
                                        <form action="{{ route('hourly-production.daily-targets') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="manufacturing_job_id" value="{{ $selectedJobId }}">
                                            <input type="hidden" name="production_date" value="{{ $selectedDate }}">
                                            <input type="hidden" name="line_name" value="{{ $selectedLine }}">
                                            <div class="table-responsive mb-3">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Line</th>
                                                            <th>Job target (total)</th>
                                                            <th>Already produced</th>
                                                            <th>දිනයේ target (pcs)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($assignedLines as $line)
                                                            @php $st = $lineStats[$line] ?? []; @endphp
                                                            <tr>
                                                                <td class="fw-semibold">{{ $line }}</td>
                                                                <td>{{ $st['job_target'] ?? 0 }}</td>
                                                                <td>{{ $st['cumulative_produced'] ?? 0 }}</td>
                                                                <td>
                                                                    <input type="number" name="line_targets[{{ $line }}]"
                                                                        class="form-control form-control-sm"
                                                                        min="0"
                                                                        value="{{ $st['suggested_daily_target'] ?? 0 }}"
                                                                        required>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="submit" class="btn btn-warning fw-bold">
                                                <i class="bi bi-check2-circle me-1"></i> Save daily targets & continue
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="card border-primary border-2 mb-4">
                                    <div class="card-header bg-primary bg-opacity-10 py-2">
                                        <strong>දිනයේ targets — {{ \Carbon\Carbon::parse($selectedDate)->format('l, d M Y') }}</strong>
                                        <span class="badge bg-secondary ms-2">Job: {{ $job->job_number ?? '' }}</span>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Line</th>
                                                        <th>දිනයේ target</th>
                                                        <th>Today produced</th>
                                                        <th>Balance (today)</th>
                                                        <th>Job total / done</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($assignedLines as $line)
                                                        @php
                                                            $st = $lineStats[$line] ?? [];
                                                            $dailyT = (int) ($st['daily_target'] ?? 0);
                                                            $todayP = (int) ($st['today_produced'] ?? 0);
                                                            $balToday = max(0, $dailyT - $todayP);
                                                        @endphp
                                                        <tr class="{{ ($selectedLine ?? '') === $line ? 'table-active' : '' }}">
                                                            <td class="fw-semibold">{{ $line }}</td>
                                                            <td><span class="badge bg-primary">{{ $dailyT }}</span></td>
                                                            <td><span class="badge bg-success">{{ $todayP }}</span></td>
                                                            <td><span class="badge {{ $balToday > 0 ? 'bg-warning text-dark' : 'bg-secondary' }}">{{ $balToday }}</span></td>
                                                            <td class="small text-muted">{{ $st['cumulative_produced'] ?? 0 }} / {{ $st['job_target'] ?? 0 }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2"><strong>Step 2:</strong> පහළින් hourly output enter කරන්න.</p>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <hr>

                        @if(!$selectedJobId)
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-briefcase" style="font-size:2.5rem;opacity:.4;"></i>
                                <p class="mt-3 mb-0 fw-semibold">Select a job above to start entering hourly production.</p>
                            </div>
                        @elseif($assignedLines->isEmpty())
                            <div class="alert alert-warning mb-0">
                                This job has no line assignments. Assign lines on the job page first.
                            </div>
                        @elseif($needsTargetSetup)
                            <div class="alert alert-secondary mb-0">
                                <i class="bi bi-arrow-up me-1"></i> Hourly output ඇතුළත් කරන්න පළමුව ඉහළින් <strong>දිනයේ target</strong> save කරන්න.
                            </div>
                        @else

                    <form action="{{ route('hourly-production.store') }}" method="POST" id="hourlyProductionForm">
                        @csrf
                        <input type="hidden" name="manufacturing_job_id" value="{{ $selectedJobId }}">
                        <input type="hidden" name="line_name" value="{{ $selectedLine }}">
                        <input type="hidden" name="production_date" value="{{ $selectedDate }}">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="alert alert-success text-white mb-0 flex-grow-1 me-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-graph-up-arrow me-2"></i> Total Job Production (all dates)</span>
                                    <h3 class="font-weight-bolder text-white mb-0">{{ $totalProduced }} Pcs</h3>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('hourly-production.print', ['job_id' => $selectedJobId, 'production_date' => $selectedDate]) }}"
                                   target="_blank"
                                   class="btn btn-outline-secondary btn-lg mb-0">
                                    <i class="bi bi-printer me-1"></i> Print Report
                                </a>
                                @if($job)
                                <button type="button" class="btn btn-outline-warning btn-lg mb-0"
                                    data-bs-toggle="modal" data-bs-target="#washingModal">
                                    <i class="bi bi-box-seam me-2"></i> Send to Washing Stock
                                </button>
                                <a href="{{ route('manufacturing.washing-stock.show', $job) }}" class="btn btn-outline-primary btn-lg mb-0">
                                    Washing Stock
                                </a>
                                @endif
                            </div>
                        </div>

                        @php
                            $startHour   = 8;
                            $endHour     = 20;
                            $currentHour = max($startHour, min($endHour, (int) date('H')));
                        @endphp

                        @if($selectedLine)
                            <div class="alert alert-light border mb-3 py-2">
                                Recording for: <strong>{{ $selectedLine }}</strong>
                            </div>
                        @endif

                        <ul class="nav nav-tabs mb-3" id="hourTabs" role="tablist">
                            @for($h = $startHour; $h <= $endHour; $h++)
                                @php
                                    $lineQty = array_key_exists($h, $hourTotals) ? (int) $hourTotals[$h] : 0;
                                    $hourIsLocked = array_key_exists($h, $hourTotals);
                                    $otherLinesHaveData = false;
                                    foreach ($assignedLines as $ln) {
                                        if ($ln !== $selectedLine && !empty($hourTotalsByLine[$ln][$h])) {
                                            $otherLinesHaveData = true;
                                            break;
                                        }
                                    }
                                @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $h == $currentHour ? 'active' : '' }} {{ $lineQty > 0 ? 'text-success fw-bold' : '' }}"
                                            id="tab-{{ $h }}"
                                            data-bs-toggle="tab"
                                            data-bs-target="#content-{{ $h }}"
                                            type="button" role="tab">
                                        {{ sprintf('%02d:00', $h) }}&ndash;{{ sprintf('%02d:00', $h+1) }}
                                        @if($hourIsLocked)
                                            <span class="badge bg-success ms-1"><i class="bi bi-lock-fill"></i> {{ $lineQty }}</span>
                                        @elseif($otherLinesHaveData)
                                            <span class="badge bg-secondary ms-1" title="Other lines have data">•</span>
                                        @endif
                                    </button>
                                </li>
                            @endfor
                        </ul>

                        <div class="tab-content" id="hourTabsContent">
                            @for($h = $startHour; $h <= $endHour; $h++)
                                <div class="tab-pane fade {{ $h == $currentHour ? 'show active' : '' }}"
                                     id="content-{{ $h }}" role="tabpanel">
                                    <div class="card border shadow-none">
                                        <div class="card-body">
                                            <h6 class="mb-3 text-muted">
                                                Production for
                                                <strong class="text-dark">{{ sprintf('%02d:00', $h) }} – {{ sprintf('%02d:00', $h+1) }}</strong>
                                                @if($selectedLine)
                                                    — <span class="badge bg-secondary">{{ $selectedLine }}</span>
                                                @endif
                                            </h6>

                                            @php
                                                $saved = array_key_exists($h, $hourTotals) ? $hourTotals[$h] : null;
                                                $isLocked = $saved !== null;
                                            @endphp
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <label class="form-label fw-semibold">Total Production (Pcs)</label>
                                                    <div class="input-group input-group-lg">
                                                        @if($isLocked)
                                                            <span class="input-group-text bg-success text-white border-success">
                                                                <i class="bi bi-lock-fill"></i>
                                                            </span>
                                                            <input type="hidden" name="hour_totals[{{ $h }}]" value="{{ $saved }}">
                                                        @endif
                                                        <input type="number"
                                                               @if(!$isLocked) name="hour_totals[{{ $h }}]" @endif
                                                               class="form-control border {{ $isLocked ? 'border-success bg-light' : '' }}"
                                                               min="0"
                                                               placeholder="{{ $isLocked ? '' : 'Enter total pcs produced' }}"
                                                               value="{{ $isLocked ? $saved : '' }}"
                                                               @if($isLocked) readonly disabled tabindex="-1" @endif>
                                                    </div>
                                                    @if($isLocked)
                                                        <small class="text-success"><i class="bi bi-lock me-1"></i>Locked — saved {{ $saved }} pcs (cannot edit)</small>
                                                    @else
                                                        <small class="text-muted">Leave blank if no production in this hour.</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Save All Hours
                            </button>
                        </div>

                    </form>
                        @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($job) && $job)
<div class="modal fade" id="washingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send to Washing Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('manufacturing.washing-stock.deposit', $job) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @php
                        $produced = $job->totalLineHourlyQuantity();
                        $received = $job->washingStockReceived();
                        $balance  = $job->availableForWashingStockDeposit();
                    @endphp
                    <div class="alert alert-info text-dark text-sm mb-3">
                        <div>Total produced: <strong>{{ $produced }}</strong></div>
                        <div>Already in washing stock: <strong>{{ $received }}</strong></div>
                        <div class="mt-1">Available to deposit: <strong class="text-lg">{{ $balance }}</strong> pcs</div>
                    </div>
                    <p class="small text-muted mb-3">Gate pass (vehicle, driver sign, print) is created from <a href="{{ route('manufacturing.washing-stock.show', $job) }}">Washing Stock</a> after this.</p>
                    <div class="mb-3">
                        <label class="form-label">Item qty <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" min="1" max="{{ $balance }}" value="{{ $balance }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning" @disabled($balance < 1)>Add to Washing Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const jobSelect  = document.getElementById('jobSelect');
    const datePicker = document.getElementById('productionDatePicker');
    const lineSelect = document.getElementById('lineSelect');

    function buildUrl() {
        const jobId = jobSelect ? jobSelect.value : '';
        if (!jobId) return null;
        const params = new URLSearchParams();
        params.set('job_id', jobId);
        if (datePicker && datePicker.value) {
            params.set('production_date', datePicker.value);
        }
        if (lineSelect && lineSelect.value) {
            params.set('line_name', lineSelect.value);
        }
        return '?' + params.toString();
    }

    function reload() {
        const url = buildUrl();
        if (url) window.location.href = url;
    }

    if (datePicker) {
        datePicker.addEventListener('change', reload);
    }

    if (jobSelect) {
        jobSelect.addEventListener('change', function () {
            if (datePicker) {
                datePicker.disabled = !this.value;
                if (!this.value && datePicker.options.length) {
                    datePicker.innerHTML = '<option value="">Select job first</option>';
                }
            }
            if (lineSelect) lineSelect.disabled = !this.value;
            reload();
        });
    }

    if (lineSelect) {
        lineSelect.addEventListener('change', reload);
    }
});

function validateWashingQty(input, max) {
    const errorDiv = document.getElementById('washQtyError');
    if (parseInt(input.value) > max) {
        input.value = max;
        errorDiv.style.display = 'block';
    } else {
        errorDiv.style.display = 'none';
    }
}
</script>
@endsection

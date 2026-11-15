@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('washing_print_url'))
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>Washing gate pass created successfully.</span>
            <a href="{{ session('washing_print_url') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-file-pdf me-1"></i> Download Gate Pass PDF
            </a>
        </div>
    @endif
    <div class="row">
        <!-- Job Header & Status -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <h4 class="mb-0">{{ $job->job_number }} <span class="text-muted text-sm">| {{ $job->style_ref }}</span></h4>
                            <p class="text-sm mb-0">Created on {{ $job->issue_date ? $job->issue_date->format('d M Y') : $job->created_at->format('d M Y') }} by {{ $job->creator?->name ?? 'System' }}</p>
                        </div>
                        <div class="col-4 text-end">
                            <span class="badge badge-lg {{ $job->status === \App\Models\ManufacturingJob::STATUS_JOB_ENDED ? 'bg-gradient-dark' : 'bg-gradient-success' }}">
                                {{ \App\Models\ManufacturingJob::statusLabel($job->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php $estMaterialCost = $job->estimatedMaterialCost(); @endphp
        @if($estMaterialCost > 0)
        <div class="col-12 mb-3">
            <div class="card border-success border-2 shadow-sm">
                <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold text-dark">Estimated material cost (whole job)</div>
                        <div class="small text-muted mb-0">Material issues × unit price (fabric + accessories). Washing gate pass table එකේත් එම value එක පෙන්වනවා.</div>
                    </div>
                    <div class="h4 mb-0 text-success font-monospace">Rs {{ number_format($estMaterialCost, 2) }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Job Timeline / Workflow Visualization -->
        <div class="col-12 mb-4">
            <style>
                .stepper-wrapper {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    position: relative;
                }
                .stepper-item {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    flex: 1;
                    z-index: 2;
                }
                .stepper-item::before {
                    position: absolute;
                    content: "";
                    border-bottom: 2px solid #e0e0e0;
                    width: 100%;
                    top: 15px;
                    left: -50%;
                    z-index: 1;
                }
                .stepper-item::after {
                    position: absolute;
                    content: "";
                    border-bottom: 2px solid #e0e0e0;
                    width: 100%;
                    top: 15px;
                    left: 50%;
                    z-index: 1;
                }
                .stepper-item .step-counter {
                    position: relative;
                    z-index: 5;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    background: #ccc;
                    color: #fff;
                    font-weight: bold;
                    margin-bottom: 6px;
                }
                .stepper-item.completed .step-counter {
                    background-color: #28a745; /* Success Green */
                }
                .stepper-item.completed::after, .stepper-item.completed::before {
                    border-color: #28a745;
                }
                .stepper-item.active .step-counter {
                    background-color: #4e73df; /* Primary Blue */
                }
                .stepper-item:first-child::before { content: none; }
                .stepper-item:last-child::after { content: none; }
                .step-name { font-size: 0.85rem; color: #666; font-weight: 600; }
                .stepper-item.active .step-name { color: #4e73df; font-weight: 700; }
            </style>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    @include('erp.jobs.partials.workflow-stepper', ['job' => $job])
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-dark fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Next Step Actions</h6>
                        <span class="text-xs text-muted">Any stage — use the action you need (no need to end the job)</span>
                    </div>
                    @include('erp.jobs.partials.workflow-actions', ['job' => $job, 'recipeProducts' => $recipeProducts])
                </div>
            </div>
        </div>

        <!-- Stage Data: Fabric Issue -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Fabric Issue Details</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Fabric:</strong> {{ $job->materialIssue?->material?->name ?? 'N/A' }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Issued Quantity:</strong> {{ $job->issued_fabric_quantity }} ({{ $job->materialIssue?->material?->unit ?? 'Units' }})
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Accessories:</strong> {{ $job->accessories ?? 'None' }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Recipe / Product:</strong>
                            @if($job->hasRecipeProduct())
                                <span class="text-success">{{ $job->product?->name ?? 'Selected' }}</span>
                            @else
                                <span class="text-muted">Not selected yet (before cutting)</span>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Stage Data: Cutting (Only if started/completed) -->
        @if($job->fabric_used_qty || $job->status == 'CUTTING_IN_PROGRESS')
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Cutting Section</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Fabric Used:</strong> {{ $job->fabric_used_qty ?? 'Pending' }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Waste:</strong> {{ $job->fabric_waste_qty ?? '0' }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Total Cut Pieces:</strong> {{ $job->total_cut_pieces ?? 'Pending' }}
                        </li>
                         <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Reject:</strong> {{ $job->cutting_reject_qty ?? '0' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Stage Data: Line Production -->
        @if($job->status != 'FABRIC_ISSUED' && $job->status != 'CUTTING_IN_PROGRESS' && $job->status != 'SENT_TO_CUTTING')
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Line Production Summary</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Assigned Qty:</strong> {{ $job->lineAssignments->sum('assigned_quantity') }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Total Produced:</strong> {{ $job->totalLineHourlyQuantity() }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Efficiency:</strong> 
                            @php
                                $assigned = $job->lineAssignments->sum('assigned_quantity');
                                $produced = $job->totalLineHourlyQuantity();
                                $eff = $assigned > 0 ? ($produced / $assigned) * 100 : 0;
                            @endphp
                            {{ number_format($eff, 1) }}%
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Stage Data: Washing -->
        @if($job->status == 'WASHING_OUT' || $job->status == 'AFTER_WASH_RECEIVED' || $job->status == 'PACKING_COMPLETED' || $job->status == 'WAREHOUSE_RECEIVED')
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Washing / Gate Pass</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Total Sent:</strong> {{ $job->totalWashingSentQuantity() }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Returned (QC Ready):</strong> {{ $job->totalWashingReturnedQuantity() }}
                        </li>
                         <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Status:</strong> 
                            @if($job->washingTransfers->whereNull('parent_id')->whereIn('status', ['pending', 'received'])->count() > 0)
                                <span class="badge bg-warning">In wash / pending</span>
                            @else
                                <span class="badge bg-success">All received back</span>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Stage Data: QC & Packing -->
        @if($job->status == 'PACKING_COMPLETED' || $job->status == 'WAREHOUSE_RECEIVED')
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>QC & Packing</h6>
                </div>
                <div class="card-body">
                     @php
                        // Aggregating QC checks from all washing transfers linked to this job
                        $totalFinished = 0;
                        $totalDamaged = 0;
                        foreach($job->washingTransfers as $transfer) {
                            $totalFinished += $transfer->qcChecks->sum('finished_good_qty');
                            $totalDamaged += $transfer->qcChecks->sum('damaged_qty');
                        }
                    @endphp
                    <ul class="list-group">
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Finished Goods:</strong> {{ $totalFinished }}
                        </li>
                        <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Damaged/Reject:</strong> {{ $totalDamaged }}
                        </li>
                         <li class="list-group-item border-0 ps-0 text-sm">
                            <strong class="text-dark">Final Output:</strong> {{ $totalFinished }} Pcs
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Old Action Buttons removed (moved to top toolbar) -->
    </div>
    <!-- Modals -->

    <!-- Reopen Manufacturing Modal -->
    <div class="modal fade" id="reopenJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reopen Manufacturing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('jobs.reopen', $job->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-sm text-muted">Choose where to continue. Cutting සිට line work දක්වා නැවත work කරන්න පුළුවන්.</p>
                        <div class="mb-3">
                            <label class="form-label">Continue from stage</label>
                            <select name="target_stage" class="form-select border px-2" required>
                                @foreach(\App\Models\ManufacturingJob::reopenStageOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="clear_line_assignments" value="1" id="clearLineAssignments">
                            <label class="form-check-label" for="clearLineAssignments">
                                Clear existing line assignments (re-assign lines from scratch)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reopen Job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Cutting Modal -->
    <div class="modal fade" id="cuttingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Cutting Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('manufacturing.cutting.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="job_id" value="{{ $job->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Fabric Used (yards)</label>
                            <input type="number" step="0.01" name="fabric_used_qty" class="form-control border px-2" required value="{{ $job->fabric_used_qty }}">
                        </div>
                        <div class="mb-3">
                            <label>Fabric Waste (yards)</label>
                            <input type="number" step="0.01" name="fabric_waste_qty" class="form-control border px-2" value="{{ $job->fabric_waste_qty }}">
                        </div>
                        <div class="mb-3">
                            <label>Total Cut Pieces</label>
                            <input type="number" name="total_cut_pieces" class="form-control border px-2" required value="{{ $job->total_cut_pieces }}">
                        </div>
                        <div class="mb-3">
                            <label>Reject Pieces</label>
                            <input type="number" name="cutting_reject_qty" class="form-control border px-2" value="{{ $job->cutting_reject_qty }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">{{ $job->hasCuttingData() ? 'Update Cutting' : 'Save & Complete Cutting' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Hourly Production Modal -->
    <div class="modal fade" id="hourlyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Hourly Production</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hourly-production.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="manufacturing_job_id" value="{{ $job->id }}">
                    
                    <div class="modal-body">
                         <!-- Grand Total Display -->
                         @php
                            $grandTotal = $job->totalLineHourlyQuantity();
                            // [date][line][hour] => qty for lock + display per line
                            $productionByLineDate = [];
                            foreach ($job->hourlyProductions as $hp) {
                                if (is_null($hp->employee_id) && $hp->line_name) {
                                    $d = \Carbon\Carbon::parse($hp->production_date)->format('Y-m-d');
                                    $productionByLineDate[$d][$hp->line_name][$hp->hour] = $hp->quantity;
                                }
                            }
                            $hours = range(8, 20);
                            $currentHour = max(8, min(20, (int) date('H')));
                        @endphp
                        <div class="alert alert-success text-white mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-graph-up-arrow me-2"></i> Total Job Production</span>
                                <h3 class="font-weight-bolder text-white mb-0">{{ $grandTotal }} Pcs</h3>
                            </div>
                        </div>

                        <div class="row mb-3">
                             <div class="col-md-6">
                                <label>Date</label>
                                <input type="date" name="production_date" id="hourlyDateInput" class="form-control border px-2" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label>Line</label>
                                <select name="line_name" id="hourlyLineSelect" class="form-select border px-2" required>
                                    @foreach($job->lineAssignments as $assign)
                                        <option value="{{ $assign->line_name }}">{{ $assign->line_name }}</option>
                                    @endforeach
                                    @if($job->lineAssignments->isEmpty())
                                        <option value="Default Line">Default Line</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <ul class="nav nav-tabs mb-3" id="productionTabs" role="tablist">
                            @foreach($hours as $h)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link hour-tab-btn {{ $h === $currentHour ? 'active' : '' }}"
                                            data-hour="{{ $h }}"
                                            id="tab-{{ $h }}" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#content-{{ $h }}" 
                                            type="button" 
                                            role="tab">
                                        {{ sprintf('%02d:00', $h) }} - {{ sprintf('%02d:00', $h+1) }}
                                        <span class="badge bg-success ms-1 hour-tab-badge d-none" data-hour="{{ $h }}"></span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content border border-top-0 p-3" id="productionTabsContent">
                            @foreach($hours as $h)
                                <div class="tab-pane fade {{ $h === $currentHour ? 'show active' : '' }}" 
                                     id="content-{{ $h }}" 
                                     role="tabpanel">
                                    
                                    <h6 class="text-sm text-muted mb-3">Production for {{ sprintf('%02d:00', $h) }} - {{ sprintf('%02d:00', $h+1) }}</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Line Total Output (Pcs)</label>
                                            <div class="hour-input-wrap" data-hour="{{ $h }}"></div>
                                            <small class="hour-status-msg text-muted" data-hour="{{ $h }}">Leave blank if no production in this hour.</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-info">Save All Production</button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const allData = @json($productionByLineDate);
                        const dateInput = document.getElementById('hourlyDateInput');
                        const lineSelect = document.getElementById('hourlyLineSelect');
                        const wraps = document.querySelectorAll('.hour-input-wrap');
                        const statusMsgs = document.querySelectorAll('.hour-status-msg');
                        const hourTabs = document.querySelectorAll('.hour-tab-btn');
                        const badges = document.querySelectorAll('.hour-tab-badge');

                        function setLockedInput(wrap, hour, value) {
                            wrap.innerHTML = '';
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'hour_totals[' + hour + ']';
                            hidden.value = value;
                            const input = document.createElement('input');
                            input.type = 'number';
                            input.className = 'form-control border px-2 border-success bg-light';
                            input.value = value;
                            input.readOnly = true;
                            input.disabled = true;
                            input.tabIndex = -1;
                            wrap.appendChild(hidden);
                            wrap.appendChild(input);
                        }

                        function setEditableInput(wrap, hour) {
                            wrap.innerHTML = '';
                            const input = document.createElement('input');
                            input.type = 'number';
                            input.name = 'hour_totals[' + hour + ']';
                            input.className = 'form-control border px-2 hour-total-input';
                            input.setAttribute('data-hour', hour);
                            input.min = '0';
                            input.placeholder = 'Enter total line output';
                            wrap.appendChild(input);
                        }

                        function updateUI() {
                            const date = dateInput?.value || '';
                            const line = lineSelect?.value || '';
                            const lineData = (allData[date] && allData[date][line]) ? allData[date][line] : {};

                            wraps.forEach(function (wrap) {
                                const h = wrap.getAttribute('data-hour');
                                const msg = document.querySelector('.hour-status-msg[data-hour="' + h + '"]');
                                const locked = Object.prototype.hasOwnProperty.call(lineData, h);

                                if (locked) {
                                    setLockedInput(wrap, h, lineData[h]);
                                    if (msg) {
                                        msg.className = 'hour-status-msg text-success';
                                        msg.innerHTML = '<i class="bi bi-lock me-1"></i>Locked — saved ' + lineData[h] + ' pcs';
                                    }
                                } else {
                                    setEditableInput(wrap, h);
                                    if (msg) {
                                        msg.className = 'hour-status-msg text-muted';
                                        msg.textContent = 'Leave blank if no production in this hour.';
                                    }
                                }
                            });

                            hourTabs.forEach(function (tab) {
                                const h = tab.getAttribute('data-hour');
                                const locked = Object.prototype.hasOwnProperty.call(lineData, h);
                                tab.classList.toggle('text-success', locked);
                                tab.classList.toggle('fw-bold', locked);
                            });

                            badges.forEach(function (badge) {
                                const h = badge.getAttribute('data-hour');
                                const locked = Object.prototype.hasOwnProperty.call(lineData, h);
                                if (locked) {
                                    badge.textContent = lineData[h];
                                    badge.classList.remove('d-none');
                                } else {
                                    badge.classList.add('d-none');
                                }
                            });
                        }

                        if (dateInput) dateInput.addEventListener('change', updateUI);
                        if (lineSelect) lineSelect.addEventListener('change', updateUI);
                        updateUI();
                    });
                </script>
            </div>
        </div>
    </div>


    <!-- Washing Modal -->
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
                            $depositBal = $job->availableForWashingStockDeposit();
                        @endphp
                        <div class="alert alert-info text-dark text-sm mb-3">
                            <div>Total produced: <strong>{{ $produced }}</strong></div>
                            <div>Already in washing stock: <strong>{{ $received }}</strong></div>
                            <div class="mt-1">Available to deposit: <strong>{{ $depositBal }}</strong> pcs</div>
                        </div>
                        <p class="small text-muted">This only puts pieces into Washing Stock. Create the vehicle / driver gate pass from <a href="{{ route('manufacturing.washing-stock.show', $job) }}">Washing Stock</a>.</p>
                        <div class="mb-3">
                            <label class="form-label">Item qty <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" max="{{ $depositBal }}" value="{{ $depositBal }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning" @disabled($depositBal < 1)>Add to Washing Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QC Modal -->
    <div class="modal fade" id="qcModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QC & Packing Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('manufacturing.qc.save') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Returned Washing Batch</label>
                            <select name="washing_transfer_id" class="form-select border px-2" required>
                                <option value="">-- Select Batch --</option>
                                @foreach($job->washingTransfers->where('status', 'washing_completed') as $transfer)
                                    <option value="{{ $transfer->id }}">
                                        Batch #{{ $transfer->id }} (Sent: {{ $transfer->quantity_sent }} on {{ $transfer->created_at->format('d M') }})
                                    </option>
                                @endforeach
                            </select>
                            @php $qcReady = $job->washingTransfers->where('status', 'washing_completed'); @endphp
                            @if($qcReady->isEmpty())
                                <small class="text-danger">No washing batches ready for QC. Complete washing first, or use <a href="{{ route('manufacturing.qc') }}">QC page</a>.</small>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Total Pieces Checked</label>
                                <input type="number" name="total_checked" class="form-control border px-2" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Finished (Good)</label>
                                <input type="number" name="finished_good_qty" class="form-control border px-2" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Damaged / Reject</label>
                                <input type="number" name="damaged_qty" class="form-control border px-2" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        @if($qcReady->isNotEmpty())
                            <button type="submit" class="btn btn-success">Save QC Record</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

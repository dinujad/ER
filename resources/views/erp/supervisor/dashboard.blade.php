@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('washing_print_url'))
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>Washing gate pass created successfully.</span>
            <a href="{{ session('washing_print_url') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-printer me-1"></i> Print Gate Pass
            </a>
        </div>
    @endif
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white shadow-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-white font-weight-bolder">{{ $section->name }} Dashboard</h3>
                            <p class="mb-0 text-white opacity-8">Supervisor: {{ $employee->name }}</p>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#changeMyPasswordModal">
                                <i class="bi bi-key me-1"></i> Change my password
                            </button>
                            <a href="{{ route('supervisor.employees') }}" class="btn btn-light btn-sm">
                                <i class="bi bi-people me-1"></i> Section employees
                            </a>
                            <i class="bi bi-diagram-3-fill text-white opacity-6" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Change my password modal --}}
    <div class="modal fade" id="changeMyPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('supervisor.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Change my password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current password</label>
                            <input type="password" name="current_password" class="form-control" required>
                            @error('current_password')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="password" class="form-control" required>
                            @error('password')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm new password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Section employees summary --}}
    @if($sectionEmployees->isNotEmpty())
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Section employees ({{ $sectionEmployees->count() }})</span>
                    <a href="{{ route('supervisor.employees') }}" class="btn btn-sm btn-outline-primary">Manage & edit passwords</a>
                </div>
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($sectionEmployees->take(10) as $emp)
                            <span class="badge bg-light text-dark border">{{ $emp->name }}</span>
                        @endforeach
                        @if($sectionEmployees->count() > 10)
                            <span class="badge bg-secondary">+{{ $sectionEmployees->count() - 10 }} more</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats or Actions based on Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h5>Pending Tasks / Active Jobs</h5>
        </div>
    </div>

    <div class="row">
        @forelse($jobs as $job)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary">{{ $job->job_number }}</span>
                    <span class="badge {{ $job->status === \App\Models\ManufacturingJob::STATUS_CUTTING_COMPLETED ? 'bg-success' : 'bg-secondary' }} text-xs">{{ \App\Models\ManufacturingJob::statusLabel($job->status) }}</span>
                </div>
                <div class="card-body">
                    <h6 class="mb-1">{{ $job->style_ref }}</h6>
                    <p class="text-xs text-secondary mb-3">{{ $job->batch_ref }}</p>

                    @if($section->production_stage_id && $job->product_id)
                        <div class="d-grid gap-2 mb-2">
                            <a href="{{ route('supervisor.usage.form', $job) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-seam me-1"></i> Record ingredient usage
                            </a>
                        </div>
                    @endif
                    <!-- Dynamic Actions based on Section -->
                    @if($section->slug == 'cutting')
                        <div class="d-grid gap-2">
                            @if($job->status === \App\Models\ManufacturingJob::STATUS_SENT_TO_CUTTING || $job->status === \App\Models\ManufacturingJob::STATUS_CUTTING_IN_PROGRESS)
                                <button class="btn btn-sm btn-outline-primary mb-0" data-bs-toggle="modal" data-bs-target="#cuttingModal" onclick="setModalJob('{{ $job->id }}')">
                                    Record Cutting
                                </button>
                            @elseif($job->status === \App\Models\ManufacturingJob::STATUS_CUTTING_COMPLETED)
                                <p class="small text-success mb-0"><i class="bi bi-check-circle me-1"></i> Cutting section complete</p>
                                <a href="{{ route('supervisor.jobs') }}" class="btn btn-sm btn-outline-secondary">View details</a>
                            @endif
                        </div>
                    @elseif($section->type == 'line')
                        <div class="d-grid gap-2">
                            @if($job->status === \App\Models\ManufacturingJob::STATUS_LINE_COMPLETED)
                                @php
                                    $produced = $job->totalLineHourlyQuantity();
                                    $received = $job->washingStockReceived();
                                    $balance = $job->availableForWashingStockDeposit();
                                @endphp
                                @if($balance > 0)
                                    <button type="button" class="btn btn-sm btn-success mb-0" data-bs-toggle="modal" data-bs-target="#gatePassModal-{{ $job->id }}">
                                        <i class="bi bi-box-seam me-1"></i> Send to washing stock
                                    </button>
                                @else
                                    <p class="small text-muted mb-0">No quantity left for washing stock.</p>
                                @endif
                                <a href="{{ route('manufacturing.washing-stock.show', $job) }}" class="btn btn-sm btn-outline-primary">Washing stock / gate pass</a>
                            @else
                                <a href="{{ route('hourly-production.create', ['job_id' => $job->id]) }}" class="btn btn-sm btn-info mb-0">
                                    Record Hourly Output
                                </a>
                                <form action="{{ route('supervisor.line.complete', $job) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark line production complete for this job? You can then create washing gate pass.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success w-100 mt-1">
                                        <i class="bi bi-check-circle me-1"></i> Mark line production complete
                                    </button>
                                </form>
                            @endif
                        </div>
                    @elseif($section->slug == 'washing')
                         <div class="d-grid gap-2">
                             @if($job->washingTransfers->whereNull('parent_id')->count() > 0)
                                 @foreach($job->washingTransfers->whereNull('parent_id') as $transfer)
                                     @if($transfer->status == 'pending')
                                         <form action="{{ route('manufacturing.washing.receive', $transfer->id) }}" method="POST">
                                             @csrf
                                             <button class="btn btn-sm btn-success w-100 mb-2">
                                                 <i class="bi bi-truck me-1"></i> Send to Wash Factory (Batch #{{ $transfer->id }})
                                             </button>
                                         </form>
                                     @elseif($transfer->status == 'received')
                                         <button class="btn btn-sm btn-warning w-100 mb-2" disabled>
                                            <i class="bi bi-hourglass-split me-1"></i> In Wash Factory (Batch #{{ $transfer->id }})
                                         </button>
                                     @elseif($transfer->status == 'received_back')
                                         <form action="{{ route('manufacturing.washing.complete', $transfer->id) }}" method="POST">
                                             @csrf
                                             <button class="btn btn-sm btn-primary w-100 mb-2">
                                                <i class="bi bi-check-circle me-1"></i> Send to QC (Batch #{{ $transfer->id }})
                                             </button>
                                         </form>
                                     @endif
                                 @endforeach
                             @else
                                 <button class="btn btn-sm btn-secondary mb-0" disabled>Waiting for Gate Pass</button>
                             @endif
                        </div>
                    @elseif($section->slug == 'finishing')
                        <div class="d-grid gap-2">
                            <!-- QC Actions -->
                            @foreach($job->washingTransfers as $transfer)
                                @if($transfer->status == 'washing_completed')
                                    <a href="{{ route('manufacturing.qc.detail', $transfer->id) }}" class="btn btn-sm btn-warning mb-2">
                                        <i class="bi bi-search me-1"></i> Perform QC (Batch #{{ $transfer->id }})
                                    </a>
                                @endif
                            @endforeach

                            <!-- Packing / Final Check Actions -->
                            @php
                                $hasReadyBatches = $job->packingBatches->whereIn('status', ['sent_to_final_check'])->count() > 0;
                            @endphp
                            
                            @if($hasReadyBatches)
                                <a href="{{ route('manufacturing.final.detail', $job->id) }}" class="btn btn-sm btn-success mb-2">
                                    <i class="bi bi-clipboard-check me-1"></i> Final Verification
                                </a>
                            @endif
                            
                            @if($job->packingBatches->where('status', 'packing')->count() > 0)
                                <span class="badge bg-info mb-2">In Packing...</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-light text-center">
                No active jobs found for your section at the moment.
            </div>
        </div>
        @endforelse
    </div>
</div>

@if($section->type == 'line')
    @foreach($jobs->where('status', \App\Models\ManufacturingJob::STATUS_LINE_COMPLETED) as $gpJob)
        @php
            $produced = $gpJob->totalLineHourlyQuantity();
            $received = $gpJob->washingStockReceived();
            $balance = $gpJob->availableForWashingStockDeposit();
        @endphp
        @if($balance > 0)
<div class="modal fade" id="gatePassModal-{{ $gpJob->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send to Washing Stock — {{ $gpJob->job_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('supervisor.washing-stock.deposit', $gpJob) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-2">Puts pieces into <strong>Washing Stock</strong>. Vehicle / driver gate pass is created from Washing Stock after this.</p>
                    <div class="alert alert-info mb-3">
                        <div>Total produced: <strong>{{ $produced }}</strong></div>
                        <div>Already in stock: <strong>{{ $received }}</strong></div>
                        <div class="mt-1">Available: <strong>{{ $balance }}</strong> pcs</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item qty <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" min="1" max="{{ $balance }}" value="{{ $balance }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Add to Washing Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
        @endif
    @endforeach
@endif

@if($section->slug == 'cutting')
{{-- Cutting modal: one form, job_id set by setModalJob() when button clicked --}}
<div class="modal fade" id="cuttingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Cutting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('manufacturing.cutting.save') }}" method="POST" id="cuttingModalForm">
                @csrf
                <input type="hidden" name="job_id" id="cuttingJobId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fabric Used (yards)</label>
                        <input type="number" step="0.01" name="fabric_used_qty" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fabric Waste (yards)</label>
                        <input type="number" step="0.01" name="fabric_waste_qty" class="form-control" min="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Cut Pieces</label>
                        <input type="number" name="total_cut_pieces" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reject Pieces</label>
                        <input type="number" name="cutting_reject_qty" class="form-control" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save & Complete Cutting</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function setModalJob(jobId) {
        var el = document.getElementById('cuttingJobId');
        if (el) el.value = jobId;
    }
</script>
@endif

@endsection

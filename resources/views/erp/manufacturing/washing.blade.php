@extends('erp.layout')

@section('title', 'Washing / Gate Pass')

@section('content')
<style>
    .wash-hero {
        background: linear-gradient(120deg, #0c4a6e 0%, #0369a1 45%, #0284c7 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 24px rgba(3, 105, 161, 0.25);
    }
    .wash-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 14px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .wash-card .card-header {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #0f172a;
    }
    .gp-badge {
        font-family: ui-monospace, monospace;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
    }
    .cost-pill {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        border-radius: 8px;
        padding: 0.2rem 0.55rem;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .stage-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .wash-view-btn.active {
        color: #fff !important;
        border-color: transparent !important;
    }
    .wash-view-btn[data-target="incoming"].active { background: #0d6efd !important; }
    .wash-view-btn[data-target="progress"].active { background: #f59e0b !important; }
    .wash-view-btn[data-target="returned"].active { background: #0ea5a4 !important; }
    .wash-view-btn[data-target="completed"].active { background: #198754 !important; }
</style>

<div class="container-fluid py-3">
    @if(session('success'))<div class="alert alert-success shadow-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger shadow-sm">{{ session('error') }}</div>@endif

    <div class="wash-hero d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h1 class="h4 mb-1 fw-bold"><i class="bi bi-droplet-half me-2"></i>Washing / Gate Pass</h1>
            <p class="mb-0 opacity-90 small" style="max-width: 42rem;">
                Gate pass එකක් = production line එකෙන් washing වෙත යවන batch එකක්. පළමු <strong>Receive</strong> කරලා washing එක ඇරඹුවාම <strong>Complete &amp; Send to QC</strong> දාන්න.
                Job එකේ material issues වල unit price අනුව <strong>estimated material cost</strong> පහළින් පෙන්වනවා.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-light text-dark px-3 py-2">Pending reception: <strong>{{ $incoming->count() }}</strong></span>
            <span class="badge bg-warning text-dark px-3 py-2">In washing: <strong>{{ $inProgress->count() }}</strong></span>
            <a href="{{ route('manufacturing.washing-stock.index') }}" class="btn btn-sm btn-light text-dark fw-semibold">Washing Stock</a>
            <a href="{{ route('manufacturing.washing.report') }}" class="btn btn-sm btn-light text-dark fw-semibold"><i class="bi bi-file-earmark-bar-graph me-1"></i> Washing Report</a>
            <a href="{{ route('manufacturing.overview') }}" class="btn btn-sm btn-light text-primary fw-semibold">← Overview</a>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-outline-primary wash-view-btn active" data-target="incoming">
            Gate Pass Created
        </button>
        <button type="button" class="btn btn-outline-warning wash-view-btn" data-target="progress">
            In Wash Factory
        </button>
        <button type="button" class="btn btn-outline-info wash-view-btn" data-target="returned">
            Received Back
        </button>
        <button type="button" class="btn btn-outline-success wash-view-btn" data-target="completed">
            Sent to QC
        </button>
    </div>

    <div class="card wash-card mb-3">
        <div class="card-body py-3">
            <form action="{{ route('manufacturing.washing.receive-scan') }}" method="POST" class="row g-2 align-items-end" id="wash-scan-form">
                @csrf
                <div class="col-md-7">
                    <label class="form-label mb-1 fw-semibold">Scan Barcode (Receive from wash factory)</label>
                    <input type="text" name="barcode" id="wash-scan-input" class="form-control" placeholder="Scan gate pass barcode — popup will ask how many pcs received" required autofocus>
                    <div class="form-text small">Gatepass එක scan කරාම popup එකකින් අහනවා මේ වර කී pieces ද ලැබුණෙ කියලා. එකම gatepass එක නැවත scan කරලා තවත් pieces add කරන්න පුළුවන්.</div>
                </div>
                <div class="col-md-5">
                    <button type="submit" class="btn btn-info text-white w-100">
                        <i class="bi bi-upc-scan me-1"></i> Scan &amp; Receive Pieces
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 wash-section" id="wash-section-incoming">
            <div class="card wash-card">
                <div class="card-header py-3 d-flex align-items-center gap-2">
                    <span class="stage-dot bg-primary"></span>
                    Gate pass created <span class="text-muted fw-normal small">(Print කරලා wash factory එකට send කරන්න)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Gate pass</th>
                                    <th>Queue</th>
                                    <th>Job / product</th>
                                    <th class="text-end">Qty (pcs)</th>
                                    <th class="text-end">Est. material cost</th>
                                    <th>Sent from</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($incoming as $transfer)
                                    @php
                                        $job = $transfer->job;
                                        $cost = $job ? $job->estimatedMaterialCost() : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="gp-badge badge bg-primary-subtle text-primary border border-primary-subtle">#{{ $transfer->id }}</span>
                                        </td>
                                        <td><span class="gp-badge">{{ $transfer->batch_no ?? '—' }}</span></td>
                                        <td>
                                            @if($job)
                                                <div class="fw-semibold">{{ $job->job_number }}</div>
                                                <div class="small text-muted">{{ $job->product?->name ?? '—' }}</div>
                                                @if($job->style_ref)
                                                    <div class="small text-muted">Style: {{ $job->style_ref }}</div>
                                                @endif
                                                <a href="{{ route('jobs.show', $job) }}" class="small">Open job →</a>
                                            @else
                                                <span class="text-muted">No job linked</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold">{{ number_format($transfer->quantity_sent) }}</td>
                                        <td class="text-end">
                                            @if($job && $cost > 0)
                                                <span class="cost-pill">Rs {{ number_format($cost, 2) }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $transfer->sent_from }}</td>
                                        <td class="small text-muted">{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('manufacturing.washing.print', ['id' => $transfer->id, 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
<a href="{{ route('manufacturing.washing.print', ['id' => $transfer->id, 'format' => 'html']) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
                                            <form action="{{ route('manufacturing.washing.receive', $transfer->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Send to Wash Factory</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">Create කරපු gate passes නැහැ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 wash-section d-none" id="wash-section-progress">
            <div class="card wash-card">
                <div class="card-header py-3 d-flex align-items-center gap-2">
                    <span class="stage-dot bg-warning"></span>
                    In wash factory <span class="text-muted fw-normal small">(return එනකම් මෙතන)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Gate pass</th>
                                    <th>Queue</th>
                                    <th>Job / product</th>
                                    <th class="text-end">Qty sent (pcs)</th>
                                    <th class="text-end">Received / Remaining</th>
                                    <th class="text-end">Est. material cost</th>
                                    <th>Received</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inProgress as $transfer)
                                    @php
                                        $job = $transfer->job;
                                        $cost = $job ? $job->estimatedMaterialCost() : 0;
                                        $alreadyReceived = (int) ($transfer->quantity_returned ?? 0);
                                        $quantitySent = (int) $transfer->quantity_sent;
                                        $remainingQty = max(0, $quantitySent - $alreadyReceived);
                                        $progressPct = $quantitySent > 0 ? min(100, round(($alreadyReceived / $quantitySent) * 100)) : 0;
                                    @endphp
                                    <tr>
                                        <td><span class="gp-badge badge bg-warning-subtle text-dark border">#{{ $transfer->id }}</span></td>
                                        <td><span class="gp-badge">{{ $transfer->batch_no ?? '—' }}</span></td>
                                        <td>
                                            @if($job)
                                                <div class="fw-semibold">{{ $job->job_number }}</div>
                                                <div class="small text-muted">{{ $job->product?->name ?? '—' }}</div>
                                                <a href="{{ route('jobs.show', $job) }}" class="small">Open job →</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold">{{ number_format($quantitySent) }}</td>
                                        <td class="text-end">
                                            <div class="small">
                                                <span class="text-success fw-semibold">{{ number_format($alreadyReceived) }}</span>
                                                <span class="text-muted">/</span>
                                                <span class="text-warning fw-semibold">{{ number_format($remainingQty) }}</span>
                                                <span class="text-muted">remaining</span>
                                            </div>
                                            <div class="progress mt-1" style="height:6px;">
                                                <div class="progress-bar {{ $progressPct >= 100 ? 'bg-success' : 'bg-info' }}" role="progressbar" style="width: {{ $progressPct }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @if($job && $cost > 0)
                                                <span class="cost-pill">Rs {{ number_format($cost, 2) }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $transfer->updated_at->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('manufacturing.washing.print', ['id' => $transfer->id, 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
<a href="{{ route('manufacturing.washing.print', ['id' => $transfer->id, 'format' => 'html']) }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">දැනට wash factory එකේ jobs නැහැ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 wash-section d-none" id="wash-section-returned">
            <div class="card wash-card border-top border-info border-2">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="stage-dot bg-info"></span>
                        Received back from wash factory <span class="text-muted fw-normal small">(ready to send QC)</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Receipt</th>
                                    <th>Gate pass</th>
                                    <th>Queue / batch</th>
                                    <th>Job</th>
                                    <th class="text-end">Qty received</th>
                                    <th>Received at</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receivedBack as $transfer)
                                    @php
                                        $job = $transfer->job;
                                        $parent = $transfer->parent;
                                        $isChild = !is_null($parent);
                                        $gatePassId = $isChild ? $parent->id : $transfer->id;
                                        $gatePassQueue = $isChild ? ($parent->batch_no ?? '—') : ($transfer->batch_no ?? '—');
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($isChild)
                                                <span class="gp-badge badge bg-info-subtle text-info border border-info-subtle">Receipt #{{ $transfer->id }}</span>
                                            @else
                                                <span class="gp-badge text-muted">Full #{{ $transfer->id }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="gp-badge text-muted">GP-{{ $gatePassId }}</span>
                                            <div class="small text-muted">{{ $gatePassQueue }}</div>
                                        </td>
                                        <td><span class="gp-badge">{{ $transfer->batch_no ?? '—' }}</span></td>
                                        <td>
                                            @if($job)
                                                <a href="{{ route('jobs.show', $job) }}">{{ $job->job_number }}</a>
                                                <div class="small text-muted">{{ $job->product?->name ?? '' }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold text-success">{{ number_format($transfer->quantity_sent) }}</td>
                                        <td class="small text-muted">{{ $transfer->updated_at->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('manufacturing.washing.complete', $transfer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Send this {{ $isChild ? 'receipt batch' : 'batch' }} ({{ $transfer->quantity_sent }} pcs) to QC now?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-arrow-right-circle me-1"></i>Send {{ number_format($transfer->quantity_sent) }} pcs to QC
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">Received back records නැහැ. Wash factory එකෙන් pcs receive කරාම මෙතන පෙන්නනවා.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 wash-section d-none" id="wash-section-completed">
            <div class="card wash-card border-top border-success border-2">
                <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="stage-dot bg-success"></span>
                        Sent to QC <span class="text-muted fw-normal small">(reference — cost එක job මුළුවට)</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Receipt</th>
                                    <th>Gate pass</th>
                                    <th>Queue / batch</th>
                                    <th>Job</th>
                                    <th class="text-end">Qty sent to QC</th>
                                    <th class="text-end">Est. material cost</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($completedRecent as $transfer)
                                    @php
                                        $job = $transfer->job;
                                        $cost = $job ? $job->estimatedMaterialCost() : 0;
                                        $parent = $transfer->parent;
                                        $isChild = !is_null($parent);
                                        $gatePassId = $isChild ? $parent->id : $transfer->id;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($isChild)
                                                <span class="gp-badge badge bg-success-subtle text-success border border-success-subtle">Receipt #{{ $transfer->id }}</span>
                                            @else
                                                <span class="gp-badge text-muted">Full #{{ $transfer->id }}</span>
                                            @endif
                                        </td>
                                        <td><span class="gp-badge text-muted">GP-{{ $gatePassId }}</span></td>
                                        <td><span class="gp-badge">{{ $transfer->batch_no ?? '—' }}</span></td>
                                        <td>
                                            @if($job)
                                                <a href="{{ route('jobs.show', $job) }}">{{ $job->job_number }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold">{{ number_format($transfer->quantity_sent) }}</td>
                                        <td class="text-end">
                                            @if($job && $cost > 0)
                                                <span class="cost-pill">Rs {{ number_format($cost, 2) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $transfer->updated_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">තවම completed transfers නැහැ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Receive-from-wash popup. Auto-opens when a barcode scan succeeds and a transfer is identified. --}}
@php($scanReceipt = session('scan_receipt'))
<div class="modal fade" id="washReceiveModal" tabindex="-1" aria-labelledby="washReceiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="washReceiveForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="washReceiveModalLabel">
                        <i class="bi bi-box-arrow-in-down me-1"></i>
                        Receive from Wash Factory
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="small text-muted">Gate Pass</div>
                            <div class="fw-bold" id="washRecGatePass">—</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Queue</div>
                            <div class="fw-bold" id="washRecQueue">—</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Job</div>
                            <div class="fw-bold" id="washRecJob">—</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Product</div>
                            <div class="fw-bold" id="washRecProduct">—</div>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-3 p-2 rounded" style="background:#f1f5f9;">
                        <div class="col-4 text-center">
                            <div class="small text-muted">Sent</div>
                            <div class="fs-5 fw-bold text-dark" id="washRecQtySent">0</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="small text-muted">Already received</div>
                            <div class="fs-5 fw-bold text-success" id="washRecAlready">0</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="small text-muted">Remaining</div>
                            <div class="fs-5 fw-bold text-warning" id="washRecRemaining">0</div>
                        </div>
                    </div>

                    <label class="form-label fw-semibold" for="washRecQtyInput">
                        මේ වර factory එකට received pieces කීයක්ද?
                    </label>
                    <input type="number" min="1" step="1" name="qty_received" id="washRecQtyInput"
                           class="form-control form-control-lg text-center fw-bold" required
                           placeholder="Enter pcs received now">
                    <div class="form-text small">
                        Default = remaining qty. Partial qty enter කරාම මේ gatepass එකම නැවත scan කරන්න පුළුවන්.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-check2-circle me-1"></i> Record Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.wash-view-btn');
    const sections = {
        incoming: document.getElementById('wash-section-incoming'),
        progress: document.getElementById('wash-section-progress'),
        returned: document.getElementById('wash-section-returned'),
        completed: document.getElementById('wash-section-completed')
    };

    function showSection(target) {
        Object.keys(sections).forEach((key) => {
            sections[key].classList.toggle('d-none', key !== target);
        });
        buttons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.target === target);
        });
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', function () {
            showSection(this.dataset.target);
        });
    });

    // Always keep the scan input focused so the user can keep scanning gatepasses.
    const scanInput = document.getElementById('wash-scan-input');
    if (scanInput) {
        scanInput.focus();
        scanInput.select();
    }

    @if($scanReceipt)
        // Switch to the 'In Wash Factory' section so the user can see progress while the popup is open.
        showSection('progress');

        const receipt = @json($scanReceipt);
        const modalEl = document.getElementById('washReceiveModal');
        const form = document.getElementById('washReceiveForm');
        // Use a placeholder route so Laravel handles the correct URL prefix (e.g. /erp/...).
        const receiveQtyUrlTemplate = "{{ route('manufacturing.washing.receive-quantity', ['id' => '__ID__']) }}";
        form.action = receiveQtyUrlTemplate.replace('__ID__', receipt.transfer_id);

        document.getElementById('washRecGatePass').textContent = '#' + receipt.transfer_id;
        document.getElementById('washRecQueue').textContent = receipt.queue_no || '—';
        document.getElementById('washRecJob').textContent = receipt.job_number || '—';
        document.getElementById('washRecProduct').textContent = receipt.product_name || '—';
        document.getElementById('washRecQtySent').textContent = receipt.quantity_sent;
        document.getElementById('washRecAlready').textContent = receipt.already_received;
        document.getElementById('washRecRemaining').textContent = receipt.remaining;

        const qtyInput = document.getElementById('washRecQtyInput');
        qtyInput.value = receipt.remaining;
        qtyInput.max = receipt.remaining;

        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        modal.show();

        modalEl.addEventListener('shown.bs.modal', function () {
            qtyInput.focus();
            qtyInput.select();
        }, { once: true });

        modalEl.addEventListener('hidden.bs.modal', function () {
            // Return focus to the scan input so the next barcode can be scanned right away.
            if (scanInput) {
                scanInput.focus();
            }
        });
    @endif
});
</script>
@endsection

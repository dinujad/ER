@extends('erp.layout')

@section('content')
<style>
    .qc-hero {
        background: linear-gradient(120deg, #0f172a 0%, #1e293b 45%, #334155 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.3rem 1.5rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.2);
    }
    .qc-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 14px rgba(0, 0, 0, 0.06);
    }
    .qc-card .card-header {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
    }
    .qc-instruction li { margin-bottom: .35rem; }
</style>
<div class="container-fluid py-4">
    @if(session('success'))<div class="alert alert-success shadow-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger shadow-sm">{{ session('error') }}</div>@endif

    <div class="qc-hero mb-3 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="bi bi-clipboard2-check me-2"></i>QC & Packing</h4>
            <p class="mb-0 opacity-90 small">Washing eken awa batch quality check කරලා good / damage වෙන් කර final flow එකට move කරන්න.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-light text-dark px-3 py-2">Pending QC: <strong>{{ $pendingTransfers->count() }}</strong></span>
            <a href="{{ route('manufacturing.washing') }}" class="btn btn-sm btn-light text-dark fw-semibold">← Washing</a>
        </div>
    </div>

    <div class="qc-card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2">සිංහල උපදෙස්</h6>
            <ul class="mb-0 ps-3 qc-instruction">
                <li><strong>Washing</strong> page → <strong>Received back</strong> tab → pcs receive කරාම → <strong>Send … pcs to QC</strong> button click කරන්න (එතැනින් මෙ list එකට batch එනවා).</li>
                <li>පළමුව <strong>Process QC & Packing</strong> click කරලා batch එක open කරන්න.</li>
                <li>Received qty එකට සමානව <strong>Passed</strong> + <strong>Damaged</strong> ගණන දාන්න.</li>
                <li>Save කරාම good සහ damage packing batches auto create වෙනවා.</li>
                <li>Good batch → <strong>Final Checking</strong>; damage batch → <strong>Damage disposed / Complete</strong>.</li>
            </ul>
        </div>
    </div>

    <div class="qc-card">
        <div class="card-header py-3">
            <h6 class="mb-0">Jobs Ready for QC & Packing</h6>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
                <table class="table align-items-center mb-0 table-hover">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Job Number</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Queue</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Quantity from Washing</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Washing Completed At</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingTransfers as $transfer)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $transfer->job?->job_number ?? 'N/A' }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $transfer->job?->style_ref ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $transfer->batch_no ?? '—' }}</p>
                                        @if($transfer->parent_id)
                                            <span class="text-muted text-xxs">GP-{{ $transfer->parent_id }} receipt</span>
                                        @endif
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $transfer->quantity_sent }} Pcs</p>
                                    </td>
                                    <td>
                                        <span class="text-secondary text-xs font-weight-bold">{{ $transfer->updated_at->format('d M Y, h:i A') }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('manufacturing.qc.detail', $transfer->id) }}" class="btn btn-sm btn-info mb-0">
                                            Process QC & Packing
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-sm py-4">
                                        QC සඳහා batches නැහැ.<br>
                                        <a href="{{ route('manufacturing.washing') }}">Washing</a> → Received back tab → <strong>Send to QC</strong> click කරන්න.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
            </div>
        </div>
            </div>
</div>
@endsection

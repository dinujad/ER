@extends('erp.layout')

@section('content')
<style>
    .qc-detail-hero {
        background: linear-gradient(120deg, #14532d 0%, #166534 45%, #15803d 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.2rem 1.4rem;
        box-shadow: 0 10px 24px rgba(20, 83, 45, 0.2);
    }
    .qc-pane {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 14px rgba(0, 0, 0, 0.06);
    }
    .qc-pane .card-header {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    .qc-note li { margin-bottom: .35rem; }
</style>
<div class="container-fluid py-4">
    <div class="qc-detail-hero mb-3 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="bi bi-check2-square me-2"></i>QC & Packing - Job Processing</h4>
            <p class="mb-0 opacity-90 small">Job No: {{ $transfer->job?->job_number ?? 'N/A' }} | Queue: {{ $transfer->batch_no ?? '—' }}</p>
        </div>
        <a href="{{ route('manufacturing.qc') }}" class="btn btn-sm btn-light text-dark fw-semibold">← Back to QC List</a>
    </div>

    <div class="qc-pane mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2">සිංහල උපදෙස් (QC Detail)</h6>
            <ul class="mb-0 ps-3 qc-note">
                <li>මේ page එකේ Step 1 එකෙන් quality check save කරන්න (`Passed + Damaged = Received Qty`).</li>
                <li>Step 2: Product එකේ <strong>දැනටමත් තියෙන</strong> variations (L, M, S…) load වෙලා තියෙනවා — අලුතින් variation හදන්න ඕන නැහැ.</li>
                <li>අද pack කරන qty variation එකකට දාලා save කරන්න. Final Checking → Convert කළාම variation එකකටම stock restock වෙනවා.</li>
                <li>Manufacturing cost product එකට auto යනවා — admin product view/edit එකේ පෙනෙනවා.</li>
            </ul>
        </div>
    </div>

    <div class="qc-pane mb-4">
        <div class="card-body">
            <h5 class="mb-0">Job Number: {{ $transfer->job?->job_number ?? 'N/A' }}</h5>
            <p class="text-secondary">{{ $transfer->job ? $transfer->job->style_ref : 'No Style Ref' }}</p>
            <div class="row mt-3">
                <div class="col-md-3">
                    <label class="text-xs text-uppercase text-secondary font-weight-bolder">Quantity Received from Washing</label>
                    <h4 class="font-weight-bolder">{{ $transfer->quantity_sent }} Pcs</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Variations Panel --}}
    @if($jobProduct)
    <div class="qc-pane mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-grid-3x3-gap me-1 text-primary"></i>
                Product: {{ $jobProduct->name }}
                @if($isVariableProduct)
                    <span class="badge bg-primary ms-2">Variable</span>
                @else
                    <span class="badge bg-secondary ms-2">Simple</span>
                @endif
            </h6>
            @if($jobCostPerUnit > 0 && auth()->user()?->can_access('manufacturing.costs'))
            <span class="badge bg-success">Est. cost Rs {{ number_format($jobCostPerUnit, 2) }}/pc</span>
            @endif
            @if($productVariations->count() === 0)
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#variationPanel">
                <i class="bi bi-sliders me-1"></i> Add Variations (first time only)
            </button>
            @endif
        </div>

        @if($productVariations->count() > 0)
        <div class="card-body py-2 border-bottom bg-white">
            <div class="alert alert-info py-2 small mb-2">
                <i class="bi bi-info-circle me-1"></i>
                Product master එකේ දැනටමත් save කරපු <strong>{{ $productVariations->count() }} variations</strong> load වෙලා තියෙනවා.
                අලුත් size හදන්න ඕන නම් Products → Edit එකෙන් add කරන්න.
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Variation</th>
                            <th>SKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productVariations as $v)
                        <tr>
                            <td class="fw-semibold">{{ $v->variation_name }}</td>
                            <td class="small text-muted">{{ $v->sub_sku }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0 mt-2">
                Convert කළාම variation එකකටම wholesale stock restock + manufacturing cost auto apply වෙනවා.
                Selling price දාන්නේ <strong>admin</strong> පමණයි (Gproducts → Edit → Publish).
            </p>
        </div>
        @endif

        @if($needsVariationQty ?? false)
        <div class="card-body py-3 border-bottom bg-light">
            <h6 class="fw-bold mb-2"><i class="bi bi-box-seam me-1"></i> Good output — qty per variation</h6>
            <p class="small text-muted mb-2">
                Size/variation එකකට අද pack කරන qty දාන්න — total <strong>{{ number_format($goodOutputQty, 0) }}</strong> pcs විය යුතුයි.
                Prices මෙතැනින් දාන්න බැහැ — convert පස්සේ admin set කරයි.
            </p>
            @if($goodBatch->status === 'packing')
            <form action="{{ route('manufacturing.qc.variation-qty') }}" method="POST">
                @csrf
                <input type="hidden" name="packing_batch_id" value="{{ $goodBatch->id }}">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered bg-white mb-2">
                        <thead class="table-light">
                            <tr>
                                <th>Variation</th>
                                <th>SKU</th>
                                <th style="width:140px">Qty (pcs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productVariations as $v)
                            <tr>
                                <td class="fw-semibold">{{ $v->variation_name }}</td>
                                <td class="small text-muted">{{ $v->sub_sku }}</td>
                                <td>
                                    <input type="number" name="quantities[{{ $v->id }}]" class="form-control form-control-sm qc-var-qty"
                                        min="0" step="1" max="{{ $goodOutputQty }}"
                                        value="{{ old('quantities.'.$v->id, $packingVariationQtys->get($v->id)?->quantity ?? '') }}"
                                        placeholder="0">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="2" class="text-end">Total entered:</th>
                                <th><span id="qcVarQtyTotal">0</span> / {{ number_format($goodOutputQty, 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-save me-1"></i> Save Variation Quantities
                </button>
            </form>
            @else
            <table class="table table-sm table-bordered bg-white mb-0">
                <thead class="table-light"><tr><th>Variation</th><th>SKU</th><th class="text-end">Qty</th></tr></thead>
                <tbody>
                    @forelse($packingVariationQtys as $line)
                    <tr>
                        <td>{{ $productVariations->firstWhere('id', $line->product_variation_id)?->variation_name ?? '—' }}</td>
                        <td class="small text-muted">{{ $productVariations->firstWhere('id', $line->product_variation_id)?->sub_sku ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ number_format($line->quantity, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-muted text-center">No quantities saved.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>
        @endif

        @if($productVariations->count() === 0)
        <div class="collapse" id="variationPanel">
            <div class="card-body">
                @if(!$isVariableProduct)
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Simple product එකකට variations add කළාම Variable වෙනවා. Size වගේ attributes generate කරන්න — <strong>price දාන්න බැහැ</strong> මෙතැනින්.
                </div>
                @endif

                <div class="p-3 bg-light border rounded">
                    <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-1"></i>Add Variations (sizes only)</h6>
                    <p class="small text-muted mb-3">
                        e.g. Size: S,M,L — combinations generate වෙනවා.
                        Selling / wholesale / retail prices <strong>admin only</strong> — convert පස්සේ Products → Gproducts එකෙන්.
                    </p>

                    <div id="qc_attribute_definitions" class="mb-3"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="qcAddAttribute()">
                        <i class="bi bi-plus-lg"></i> Add Attribute
                    </button>

                    <div class="mb-3">
                        <button type="button" class="btn btn-primary btn-sm" onclick="qcGenerateMatrix()">
                            <i class="bi bi-grid-3x3"></i> Generate Variations
                        </button>
                    </div>

                    <form action="{{ route('products.variations.store_many', $jobProduct->id) }}" method="POST" id="qc_variation_form" style="display:none;">
                        @csrf
                        <input type="hidden" name="manufacturing_mode" value="1">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm bg-white" id="qc_variations_matrix">
                                <thead class="table-dark text-white">
                                    <tr>
                                        <th>Variation Name</th>
                                        <th>SKU Suffix</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm mt-2">
                            <i class="bi bi-save"></i> Save Variations
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

    </div>

    @endif

    <!-- Step 1: Quality Control -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="qc-pane">
                <div class="card-header pb-0">
                    <h6>Step 1: Quality Check</h6>
                </div>
                <div class="card-body">
                    @if($transfer->qcCheck)
                        <div class="alert alert-success text-dark">
                            <strong>QC Completed!</strong>
                            <br>
                            Checked: {{ $transfer->qcCheck->total_checked }} | 
                            Passed: {{ $transfer->qcCheck->finished_good_qty }} | 
                            Damaged: {{ $transfer->qcCheck->damaged_qty }}
                        </div>
                    @else
                        <form action="{{ route('manufacturing.qc.save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="washing_transfer_id" value="{{ $transfer->id }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Total Quantity (Received)</label>
                                    <input type="text" class="form-control" value="{{ $transfer->quantity_sent }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-success">Passed Quantity</label>
                                    <input type="number" name="finished_good_qty" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-danger">Damaged / Rejected</label>
                                    <input type="number" name="damaged_qty" class="form-control" value="0" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label>QC Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Save QC & Proceed to Packing</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Packing Accessories (Split View) -->
    @if($transfer->qcCheck)
    <div class="row">
        <!-- Good Packing -->
        <div class="col-md-6">
            <div class="card mb-4 border-success qc-pane" style="border-left: 5px solid #2dce89;">
                <div class="card-header pb-0">
                    <h6 class="text-success">Packing: Good Output ({{ $transfer->qcCheck->finished_good_qty }} Pcs)</h6>
                    <span class="badge bg-success">Batch: {{ $goodBatch->id ?? 'Creating...' }}</span>
                </div>
                <div class="card-body">
                    @if($goodBatch)
                        <!-- Accessories List -->
                        <table class="table table-sm mb-3">
                            <thead><tr><th>Item</th><th>Qty</th>@if(auth()->user()?->can_access('manufacturing.costs'))<th>Cost</th>@endif</tr></thead>
                            <tbody>
                                @foreach($goodBatch->materialIssues as $issue)
                                <tr>
                                    <td>{{ $issue->material?->name ?? '—' }}</td>
                                    <td>{{ $issue->quantity_issued }}</td>
                                    @if(auth()->user()?->can_access('manufacturing.costs'))
                                    <td>{{ number_format($issue->effectiveLineCost(), 2) }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Add Accessory Form -->
                        @if($goodBatch->status == 'packing')
                        <form action="{{ route('manufacturing.qc.accessory') }}" method="POST" class="mb-3 p-2 bg-light rounded">
                            @csrf
                            <input type="hidden" name="job_id" value="{{ $transfer->job?->id ?? '' }}">
                            <input type="hidden" name="packing_batch_id" value="{{ $goodBatch->id }}">
                            <div class="input-group input-group-sm">
                                <select name="material_id" class="form-select" required>
                                    <option value="">Add Accessory...</option>
                                    @foreach($accessories as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->name }}
                                            @if(auth()->user()?->can_access('manufacturing.costs'))
                                                (LKR {{ number_format($acc->unit_price ?? 0, 2) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" name="quantity" class="form-control" placeholder="Qty" style="max-width: 80px;" required>
                                <button class="btn btn-primary mb-0">Add</button>
                            </div>
                        </form>
                        
                        <a href="{{ route('manufacturing.packing.print', $goodBatch->id) }}" target="_blank" class="btn btn-outline-dark btn-sm w-100 mb-2">
                            <i class="fas fa-print"></i> Print Barcodes (PG-{{ $transfer->job?->job_number ?? 'N/A' }})
                        </a>

                        @if(($needsVariationQty ?? false) && $productVariations->count() > 0)
                        <div class="border rounded p-2 mb-2 bg-white">
                            <p class="small fw-semibold mb-2 text-success"><i class="bi bi-1-circle me-1"></i> Variation qty (අනිවාර්ය)</p>
                            @if(!($variationQtySaved ?? false))
                            <div class="alert alert-warning py-2 small mb-0">
                                පහල Product panel එකේ variation qty save කරන්න (total = {{ number_format($goodOutputQty, 0) }} pcs). ඊට පස්සේ Move to Final Checking.
                            </div>
                            @else
                            <div class="alert alert-success py-2 small mb-0">
                                <i class="bi bi-check-circle me-1"></i> Variation qty saved — දැන් Move to Final Checking කරන්න පුළුවන්.
                            </div>
                            @endif
                        </div>
                        @endif

                        <form action="{{ route('manufacturing.qc.complete') }}" method="POST">
                            @csrf
                            <input type="hidden" name="packing_batch_id" value="{{ $goodBatch->id }}">
                            <button type="submit" class="btn btn-success btn-sm w-100"
                                @if(($needsVariationQty ?? false) && !($variationQtySaved ?? false)) disabled title="Save variation quantities first" @endif>
                                <i class="bi bi-2-circle me-1"></i> Move to Final Checking (good)
                            </button>
                        </form>
                        @else
                            <div class="alert alert-success text-xs mb-0">Sent to Final Checking</div>
                        @endif
                    @else
                        <p class="text-sm">Batch initializing...</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Damage Packing -->
        <div class="col-md-6">
            <div class="card mb-4 border-danger qc-pane" style="border-left: 5px solid #f5365c;">
                <div class="card-header pb-0">
                    <h6 class="text-danger">Packing: Damage Output ({{ $transfer->qcCheck->damaged_qty }} Pcs)</h6>
                    <span class="badge bg-danger">Batch: {{ $damageBatch->id ?? 'Creating...' }}</span>
                </div>
                <div class="card-body">
                     @if($damageBatch)
                        <!-- Accessories List -->
                        <table class="table table-sm mb-3">
                            <thead><tr><th>Item</th><th>Qty</th>@if(auth()->user()?->can_access('manufacturing.costs'))<th>Cost</th>@endif</tr></thead>
                            <tbody>
                                @foreach($damageBatch->materialIssues as $issue)
                                <tr>
                                    <td>{{ $issue->material?->name ?? '—' }}</td>
                                    <td>{{ $issue->quantity_issued }}</td>
                                    @if(auth()->user()?->can_access('manufacturing.costs'))
                                    <td>{{ number_format($issue->effectiveLineCost(), 2) }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Add Accessory Form -->
                         @if($damageBatch->status == 'packing')
                        <form action="{{ route('manufacturing.qc.accessory') }}" method="POST" class="mb-3 p-2 bg-light rounded">
                            @csrf
                            <input type="hidden" name="job_id" value="{{ $transfer->job?->id ?? '' }}">
                            <input type="hidden" name="packing_batch_id" value="{{ $damageBatch->id }}">
                            <div class="input-group input-group-sm">
                                <select name="material_id" class="form-select" required>
                                    <option value="">Add Accessory...</option>
                                    @foreach($accessories as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->name }}
                                            @if(auth()->user()?->can_access('manufacturing.costs'))
                                                (LKR {{ number_format($acc->unit_price ?? 0, 2) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" name="quantity" class="form-control" placeholder="Qty" style="max-width: 80px;" required>
                                <button class="btn btn-primary mb-0">Add</button>
                            </div>
                        </form>

                         <a href="{{ route('manufacturing.packing.print', $damageBatch->id) }}" target="_blank" class="btn btn-outline-dark btn-sm w-100 mb-2">
                            <i class="fas fa-print"></i> Print Barcodes (PD-{{ $transfer->job?->job_number ?? 'N/A' }})
                        </a>

                        <p class="text-muted small mb-2">After good is sent to Final Checking and damage is marked done, this job will leave the QC list.</p>
                        <form action="{{ route('manufacturing.qc.complete') }}" method="POST">
                            @csrf
                            <input type="hidden" name="packing_batch_id" value="{{ $damageBatch->id }}">
                            <button class="btn btn-warning btn-sm w-100">
                                Damage disposed / Complete
                            </button>
                        </form>
                         @else
                            <div class="alert alert-warning text-xs mb-0">Damage marked done.</div>
                        @endif
                    @else
                        <p class="text-sm">No damage output.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
let qcAttrIdx = 0;
const productSku = "{{ $jobProduct?->sku ?? '' }}";

function qcAddAttribute() {
    const container = document.getElementById('qc_attribute_definitions');
    const div = document.createElement('div');
    div.className = 'row mb-2 qc-attr-row';
    div.innerHTML = `
        <div class="col-5">
            <input type="text" class="form-control form-control-sm" name="qc_attr[${qcAttrIdx}][name]" placeholder="Attribute (e.g. Size)">
        </div>
        <div class="col-6">
            <input type="text" class="form-control form-control-sm" name="qc_attr[${qcAttrIdx}][values]" placeholder="Values: S, M, L">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.row').remove()"><i class="bi bi-x"></i></button>
        </div>
    `;
    container.appendChild(div);
    qcAttrIdx++;
}

function qcGenerateMatrix() {
    const attributes = [];
    document.querySelectorAll('.qc-attr-row').forEach(row => {
        const name = row.querySelector('input[name*="[name]"]').value.trim();
        const vals = row.querySelector('input[name*="[values]"]').value;
        if (name && vals) {
            const values = vals.split(',').map(v => v.trim()).filter(Boolean);
            if (values.length) attributes.push({ name, values });
        }
    });

    if (!attributes.length) {
        alert('Add at least one attribute with values first.');
        return;
    }

    const cartesian = attributes.map(a => a.values).reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
    const tbody = document.querySelector('#qc_variations_matrix tbody');
    tbody.innerHTML = '';
    document.getElementById('qc_variation_form').style.display = 'block';

    cartesian.forEach((combo, idx) => {
        const c = Array.isArray(combo) ? combo : [combo];
        const name = c.join('-');
        const sku = c.join('').toUpperCase().replace(/\s+/g, '');
        const row = tbody.insertRow();
        row.innerHTML = `
            <td>
                <strong>${name}</strong>
                <input type="hidden" name="variations[${idx}][variation_name]" value="${name}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">${productSku}-</span>
                    <input type="text" name="variations[${idx}][sku_suffix]" class="form-control" value="${sku}">
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button>
            </td>
        `;
    });
}

function qcUpdateQtyTotal() {
    const max = {{ (float) ($goodOutputQty ?? 0) }};
    let total = 0;
    document.querySelectorAll('.qc-var-qty').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    const el = document.getElementById('qcVarQtyTotal');
    if (el) {
        el.textContent = total.toFixed(0);
        el.classList.toggle('text-danger', Math.abs(total - max) > 0.0001);
        el.classList.toggle('text-success', total > 0 && Math.abs(total - max) <= 0.0001);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    @if($jobProduct && $productVariations->count() === 0)
    const panel = document.getElementById('variationPanel');
    if (panel) new bootstrap.Collapse(panel, { show: true });
    @endif

    if (document.getElementById('qc_attribute_definitions')) qcAddAttribute();

    document.querySelectorAll('.qc-var-qty').forEach(inp => {
        inp.addEventListener('input', qcUpdateQtyTotal);
    });
    qcUpdateQtyTotal();
});
</script>
@endsection

@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">New Purchase</h4>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Back to List</a>
</div>

@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<style>
    #items-table td:first-child { min-width: 240px; }
    #items-table .select2-container--default .select2-selection--single {
        height: 31px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    #items-table .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 29px;
        padding-left: 8px;
    }
    #items-table .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 29px;
    }
</style>

<form method="POST" action="{{ route('purchases.store') }}" id="purchase-form">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Purchase Details</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id', $preselectedSupplier?->id) == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Type</label>
                            <select name="purchase_type" class="form-select" id="purchase_type">
                                <option value="credit" {{ old('purchase_type', 'credit') === 'credit' ? 'selected' : '' }}>Credit</option>
                                <option value="cash" {{ old('purchase_type') === 'cash' ? 'selected' : '' }}>Full Payment</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference No</label>
                            <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong>Items</strong>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addMaterialModal"><i class="bi bi-plus-lg me-1"></i>Add New Item</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-row">+ Add Row</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="items-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Items</th>
                                    <th>Unit</th>
                                    <th style="width:100px">Qty</th>
                                    <th style="width:110px">Buy Price</th>
                                    <th style="width:90px">Discount</th>
                                    <th>Line Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][material_id]" class="form-select form-select-sm item-material" required>
                                            <option value="">-- Select Item --</option>
                                            @foreach($materials as $m)
                                            <option value="{{ $m->id }}" data-unit="{{ $m->unit }}" data-price="{{ $m->unit_price }}">{{ $m->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][unit]" class="form-control form-control-sm item-unit" placeholder="—" readonly style="max-width:80px"></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td>
                                    <td><input type="number" name="items[0][buy_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                                    <td><input type="number" name="items[0][discount]" class="form-control form-control-sm item-disc" step="0.01" min="0" value="0"></td>
                                    <td><span class="line-total">0.00</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><strong>Summary</strong></div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between"><span class="text-muted">Subtotal</span><span id="sum-subtotal">0.00</span></div>
                    <div class="mb-2">
                        <label class="form-label small">Discount</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" min="0" value="{{ old('discount_amount', 0) }}">
                            <select name="discount_type" id="discount_type" class="form-select" style="max-width:80px">
                                <option value="amount" {{ old('discount_type', 'amount') === 'amount' ? 'selected' : '' }}>Rs</option>
                                <option value="percent" {{ old('discount_type') === 'percent' ? 'selected' : '' }}>%</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2"><label class="form-label small">Tax</label><input type="number" name="tax_amount" id="tax_amount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax_amount', 0) }}"></div>
                    <div class="mb-2"><label class="form-label small">Shipping / Other</label><input type="number" name="shipping_cost" id="shipping_cost" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('shipping_cost', 0) }}"></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2"><strong>Grand Total</strong><strong id="sum-total">0.00</strong></div>
                    @include('erp.purchases._payments')

                    <button type="submit" name="confirm" value="0" class="btn btn-outline-secondary w-100 mb-2">Save as Draft</button>
                    <button type="submit" name="confirm" value="1" class="btn btn-primary w-100">Confirm Purchase</button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Quick Add Material Modal --}}
<div class="modal fade" id="addMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="add-material-error" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="new_mat_name" class="form-control" placeholder="e.g. Cotton Fabric" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select id="new_mat_type" class="form-select" required>
                        <option value="fabric">Fabric</option>
                        <option value="accessory">Accessory</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <input type="text" id="new_mat_unit" class="form-control" placeholder="e.g. yards, pcs, kg" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit Price (optional)</label>
                    <input type="number" id="new_mat_unit_price" class="form-control" step="0.01" min="0" value="0" placeholder="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-new-material-btn">Save & Use in Purchase</button>
            </div>
        </div>
    </div>
</div>

@php
    $materialsJson = $materials->map(function ($m) {
        return ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit ?? '', 'unit_price' => $m->unit_price ?? 0];
    })->values();
@endphp
<script>
(function() {
    var materials = @json($materialsJson);
    var rowIndex = 1;

    function escapeHtml(text) {
        return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function materialOptionsHtml() {
        var opts = '<option value="">-- Select Item --</option>';
        materials.forEach(function(m) {
            opts += '<option value="' + m.id + '" data-unit="' + escapeHtml(m.unit) + '" data-price="' + (m.unit_price || 0) + '">' + escapeHtml(m.name) + '</option>';
        });
        return opts;
    }

    function initMaterialSelect2(selectEl) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            return;
        }
        var $sel = jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.select2({
            placeholder: '-- Select Item --',
            allowClear: true,
            width: '100%',
            dropdownParent: jQuery('body'),
            minimumResultsForSearch: 0,
        });
    }

    function destroyMaterialSelect2(selectEl) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            return;
        }
        var $sel = jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
    }

    function applyMaterialFromSelect(tr, selectEl) {
        var opt = selectEl.options[selectEl.selectedIndex];
        if (opt && opt.value) {
            var matId = parseInt(opt.value, 10);
            var mat = materials.find(function(m) { return m.id === matId; });
            tr.querySelector('.item-unit').value = opt.dataset.unit || (mat ? mat.unit : '') || '';
            tr.querySelector('.item-price').value = opt.dataset.price || (mat ? mat.unit_price : 0) || 0;
        }
        calcLine(tr);
        calcSummary();
    }

    function addRow() {
        var tbody = document.getElementById('items-tbody');
        var tr = document.createElement('tr');
        tr.className = 'item-row';
        var opts = materialOptionsHtml();
        tr.innerHTML = '<td><select name="items['+rowIndex+'][material_id]" class="form-select form-select-sm item-material" required>'+opts+'</select></td>' +
            '<td><input type="text" name="items['+rowIndex+'][unit]" class="form-control form-control-sm item-unit" readonly style="max-width:80px"></td>' +
            '<td><input type="number" name="items['+rowIndex+'][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td>' +
            '<td><input type="number" name="items['+rowIndex+'][buy_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>' +
            '<td><input type="number" name="items['+rowIndex+'][discount]" class="form-control form-control-sm item-disc" step="0.01" min="0" value="0"></td>' +
            '<td><span class="line-total">0.00</span></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>';
        tbody.appendChild(tr);
        rowIndex++;
        bindRow(tr);
    }
    function bindRow(tr) {
        var sel = tr.querySelector('.item-material');
        initMaterialSelect2(sel);
        if (typeof jQuery !== 'undefined') {
            jQuery(sel).off('change.purchaseMaterial').on('change.purchaseMaterial', function() {
                applyMaterialFromSelect(tr, this);
            });
        } else {
            sel.addEventListener('change', function() {
                applyMaterialFromSelect(tr, this);
            });
        }
        tr.querySelectorAll('.item-qty, .item-price, .item-disc').forEach(function(inp) {
            inp.addEventListener('input', function() { calcLine(tr); calcSummary(); });
        });
        tr.querySelector('.remove-row').addEventListener('click', function() {
            destroyMaterialSelect2(sel);
            tr.remove();
            calcSummary();
        });
    }
    function calcLine(tr) {
        var q = parseFloat(tr.querySelector('.item-qty').value) || 0;
        var p = parseFloat(tr.querySelector('.item-price').value) || 0;
        var d = parseFloat(tr.querySelector('.item-disc').value) || 0;
        var tot = Math.round((q * p - d) * 100) / 100;
        tr.querySelector('.line-total').textContent = tot.toFixed(2);
    }
    function calcSummary() {
        var subtotal = 0;
        document.querySelectorAll('.item-row').forEach(function(tr) {
            var q = parseFloat(tr.querySelector('.item-qty').value) || 0;
            var p = parseFloat(tr.querySelector('.item-price').value) || 0;
            var d = parseFloat(tr.querySelector('.item-disc').value) || 0;
            subtotal += q * p - d;
        });
        var discAmt = parseFloat(document.getElementById('discount_amount').value) || 0;
        var discType = document.getElementById('discount_type').value;
        if (discType === 'percent') discAmt = subtotal * (discAmt / 100);
        var tax = parseFloat(document.getElementById('tax_amount').value) || 0;
        var ship = parseFloat(document.getElementById('shipping_cost').value) || 0;
        var total = Math.round((subtotal - discAmt + tax + ship) * 100) / 100;
        document.getElementById('sum-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('sum-total').textContent = total.toFixed(2);
        if (typeof calcPaymentsSummary === 'function') calcPaymentsSummary();
    }
    function refreshAllMaterialDropdowns(selectId) {
        var opts = materialOptionsHtml();
        var first = true;
        document.querySelectorAll('.item-material').forEach(function(sel) {
            destroyMaterialSelect2(sel);
            var cur = sel.value;
            sel.innerHTML = opts;
            if (selectId && first) {
                sel.value = String(selectId);
                first = false;
            } else if (cur) {
                sel.value = cur;
            }
            initMaterialSelect2(sel);
            var row = sel.closest('tr');
            if (typeof jQuery !== 'undefined') {
                jQuery(sel).off('change.purchaseMaterial').on('change.purchaseMaterial', function() {
                    applyMaterialFromSelect(row, this);
                });
            }
            if (row) {
                var opt = sel.options[sel.selectedIndex];
                if (opt && opt.value) {
                    row.querySelector('.item-unit').value = opt.dataset.unit || '';
                    row.querySelector('.item-price').value = opt.dataset.price || 0;
                }
            }
        });
        document.querySelectorAll('.item-row').forEach(function(tr) { calcLine(tr); });
        calcSummary();
    }

    document.getElementById('save-new-material-btn').addEventListener('click', function() {
        var name = document.getElementById('new_mat_name').value.trim();
        var type = document.getElementById('new_mat_type').value;
        var unit = document.getElementById('new_mat_unit').value.trim();
        var unitPrice = parseFloat(document.getElementById('new_mat_unit_price').value) || 0;
        var errEl = document.getElementById('add-material-error');
        errEl.classList.add('d-none');
        if (!name) { errEl.textContent = 'Name is required.'; errEl.classList.remove('d-none'); return; }
        if (!unit) { errEl.textContent = 'Unit is required.'; errEl.classList.remove('d-none'); return; }
        var btn = this;
        btn.disabled = true;
        var csrf = document.querySelector('#purchase-form input[name="_token"]').value;
        fetch('{{ route("inventory.quick-store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name, type: type, unit: unit, unit_price: unitPrice })
        }).then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            btn.disabled = false;
            if (!res.ok) {
                errEl.textContent = (res.data && res.data.message) || (res.data && res.data.errors ? Object.values(res.data.errors).flat().join(' ') : 'Failed to save.');
                errEl.classList.remove('d-none');
                return;
            }
            var m = res.data.material;
            materials.push({ id: m.id, name: m.name, unit: m.unit || '', unit_price: m.unit_price || 0 });
            refreshAllMaterialDropdowns(m.id);
            document.getElementById('new_mat_name').value = '';
            document.getElementById('new_mat_unit').value = '';
            document.getElementById('new_mat_unit_price').value = '0';
            bootstrap.Modal.getInstance(document.getElementById('addMaterialModal')).hide();
        }).catch(function() {
            btn.disabled = false;
            errEl.textContent = 'Request failed.';
            errEl.classList.remove('d-none');
        });
    });

    document.getElementById('add-row').addEventListener('click', addRow);
    document.querySelectorAll('#discount_amount, #discount_type, #tax_amount, #shipping_cost').forEach(function(el) {
        el.addEventListener('input', calcSummary);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.item-row').forEach(bindRow);
            calcSummary();
        });
    } else {
        document.querySelectorAll('.item-row').forEach(bindRow);
        calcSummary();
    }
})();
</script>
@include('erp.purchases._payments-script')
@endsection

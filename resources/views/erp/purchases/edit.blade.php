@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Edit Purchase (Draft)</h4>
    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-secondary">Back to View</a>
</div>

@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

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

@canRoute('purchases.update')
<form method="POST" action="{{ route('purchases.update', $purchase) }}" id="purchase-form">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ old('date', $purchase->date->format('Y-m-d')) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select" required>@foreach($suppliers as $s)<option value="{{ $s->id }}" {{ old('supplier_id', $purchase->supplier_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Type</label><select name="purchase_type" id="purchase_type" class="form-select"><option value="credit" {{ old('purchase_type', $purchase->purchase_type) === 'credit' ? 'selected' : '' }}>Credit</option><option value="cash" {{ old('purchase_type', $purchase->purchase_type) === 'cash' ? 'selected' : '' }}>Full Payment</option></select></div>
                        <div class="col-md-4"><label class="form-label">Reference</label><input type="text" name="reference_no" class="form-control" value="{{ old('reference_no', $purchase->reference_no) }}"></div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $purchase->notes) }}</textarea></div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between"><strong>Items</strong><button type="button" class="btn btn-sm btn-outline-primary" id="add-row">+ Add Row</button></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="items-table">
                        <thead class="table-light"><tr><th>Items</th><th>Unit</th><th>Qty</th><th>Buy Price</th><th>Discount</th><th>Total</th><th></th></tr></thead>
                        <tbody id="items-tbody">
                            @foreach($purchase->items as $idx => $item)
                            <tr class="item-row">
                                <td><select name="items[{{ $idx }}][material_id]" class="form-select form-select-sm item-material" required><option value="">-- Select --</option>@foreach($materials as $m)<option value="{{ $m->id }}" data-unit="{{ $m->unit }}" data-price="{{ $m->unit_price }}" {{ $item->material_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach</select></td>
                                <td><input type="text" name="items[{{ $idx }}][unit]" class="form-control form-control-sm item-unit" value="{{ $item->unit }}" readonly style="max-width:80px"></td>
                                <td><input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="{{ $item->quantity }}" required></td>
                                <td><input type="number" name="items[{{ $idx }}][buy_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="{{ $item->buy_price }}" required></td>
                                <td><input type="number" name="items[{{ $idx }}][discount]" class="form-control form-control-sm item-disc" step="0.01" min="0" value="{{ $item->discount }}"></td>
                                <td><span class="line-total">{{ number_format($item->line_total, 2) }}</span></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">Discount</label><input type="number" name="discount_amount" id="discount_amount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount_amount', $purchase->discount_amount) }}"></div>
                    <div class="mb-2"><label class="form-label small">Discount Type</label><select name="discount_type" id="discount_type" class="form-select form-select-sm"><option value="amount" {{ $purchase->discount_type === 'amount' ? 'selected' : '' }}>Amount</option><option value="percent" {{ $purchase->discount_type === 'percent' ? 'selected' : '' }}>Percent</option></select></div>
                    <div class="mb-2"><label class="form-label small">Tax</label><input type="number" name="tax_amount" id="tax_amount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax_amount', $purchase->tax_amount) }}"></div>
                    <div class="mb-2"><label class="form-label small">Shipping</label><input type="number" name="shipping_cost" id="shipping_cost" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('shipping_cost', $purchase->shipping_cost) }}"></div>
                    <hr><div class="d-flex justify-content-between mb-2"><strong>Total</strong><strong id="sum-total">{{ number_format($purchase->total, 2) }}</strong></div>
                    @include('erp.purchases._payments', ['paymentRows' => $paymentRows])

                    <button type="submit" class="btn btn-primary w-100">Update Purchase</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endcanRoute
<script>
(function(){
    @php
        $materialOptions = $materials->map(fn ($m) => [
            'id' => $m->id, 'name' => $m->name,
            'unit' => $m->unit ?? '', 'unit_price' => $m->unit_price ?? 0,
        ])->values();
    @endphp
    var materials = @json($materialOptions);
    var rowIndex = {{ $purchase->items->count() }};

    function escapeHtml(text) {
        return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function materialOptionsHtml() {
        var opts = '<option value="">-- Select --</option>';
        materials.forEach(function(m) {
            opts += '<option value="' + m.id + '" data-unit="' + escapeHtml(m.unit) + '" data-price="' + (m.unit_price || 0) + '">' + escapeHtml(m.name) + '</option>';
        });
        return opts;
    }

    function initMaterialSelect2(selectEl) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        var $sel = jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.select2({ placeholder: '-- Select --', allowClear: true, width: '100%', dropdownParent: jQuery('body'), minimumResultsForSearch: 0 });
    }

    function destroyMaterialSelect2(selectEl) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        var $sel = jQuery(selectEl);
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    }

    function applyMaterialFromSelect(tr, selectEl) {
        var o = selectEl.options[selectEl.selectedIndex];
        if (o && o.value) {
            var matId = parseInt(o.value, 10);
            var mat = materials.find(function(m) { return m.id === matId; });
            tr.querySelector('.item-unit').value = o.dataset.unit || (mat ? mat.unit : '') || '';
            tr.querySelector('.item-price').value = o.dataset.price || (mat ? mat.unit_price : 0) || 0;
        }
        calcLine(tr);
        calcSummary();
    }

    function addRow(){
        var tbody=document.getElementById('items-tbody');
        var tr=document.createElement('tr');
        tr.className='item-row';
        tr.innerHTML='<td><select name="items['+rowIndex+'][material_id]" class="form-select form-select-sm item-material" required>'+materialOptionsHtml()+'</select></td><td><input type="text" name="items['+rowIndex+'][unit]" class="form-control form-control-sm item-unit" readonly style="max-width:80px"></td><td><input type="number" name="items['+rowIndex+'][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td><td><input type="number" name="items['+rowIndex+'][buy_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td><td><input type="number" name="items['+rowIndex+'][discount]" class="form-control form-control-sm item-disc" step="0.01" min="0" value="0"></td><td><span class="line-total">0.00</span></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>';
        tbody.appendChild(tr);
        rowIndex++;
        bindRow(tr);
    }

    function bindRow(tr){
        var sel = tr.querySelector('.item-material');
        initMaterialSelect2(sel);
        if (typeof jQuery !== 'undefined') {
            jQuery(sel).off('change.purchaseMaterial').on('change.purchaseMaterial', function() {
                applyMaterialFromSelect(tr, this);
            });
        } else {
            sel.addEventListener('change', function() { applyMaterialFromSelect(tr, this); });
        }
        tr.querySelectorAll('.item-qty, .item-price, .item-disc').forEach(function(i){
            i.addEventListener('input',function(){ calcLine(tr); calcSummary(); });
        });
        tr.querySelector('.remove-row').addEventListener('click',function(){
            destroyMaterialSelect2(sel);
            tr.remove();
            calcSummary();
        });
    }
    function calcLine(tr){ var q=parseFloat(tr.querySelector('.item-qty').value)||0,p=parseFloat(tr.querySelector('.item-price').value)||0,d=parseFloat(tr.querySelector('.item-disc').value)||0; tr.querySelector('.line-total').textContent=(q*p-d).toFixed(2); }
    function calcSummary(){ var st=0; document.querySelectorAll('.item-row').forEach(function(tr){ var q=parseFloat(tr.querySelector('.item-qty').value)||0,p=parseFloat(tr.querySelector('.item-price').value)||0,d=parseFloat(tr.querySelector('.item-disc').value)||0; st+=q*p-d; }); var da=parseFloat(document.getElementById('discount_amount').value)||0; if(document.getElementById('discount_type').value==='percent') da=st*da/100; var total=Math.round((st-da+(parseFloat(document.getElementById('tax_amount').value)||0)+(parseFloat(document.getElementById('shipping_cost').value)||0))*100)/100; document.getElementById('sum-total').textContent=total.toFixed(2); if(typeof calcPaymentsSummary==='function') calcPaymentsSummary(); }
    document.getElementById('add-row').addEventListener('click',addRow); document.querySelectorAll('#discount_amount, #discount_type, #tax_amount, #shipping_cost').forEach(function(e){ e.addEventListener('input',calcSummary); });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { document.querySelectorAll('.item-row').forEach(bindRow); calcSummary(); });
    } else {
        document.querySelectorAll('.item-row').forEach(bindRow); calcSummary();
    }
})();
</script>
@include('erp.purchases._payments-script')
@endsection

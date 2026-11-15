@extends('erp.layout')

@section('title', 'Stock Adjustment')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Stock Adjustment</h1>
            <p class="text-muted small mb-0">Add or subtract product stock (wholesale / retail). Variable products need a size.</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">← Products</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0" style="max-width: 820px">
        <div class="card-header bg-white fw-semibold">Manual stock correction</div>
        <div class="card-body">
            @canRoute('stock-adjustment.store')
<form action="{{ route('stock-adjustment.store') }}" method="POST" id="stock-adjust-form">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Search</label>
                    <input type="search" id="product-filter" class="form-control form-control-sm" placeholder="Type name or SKU…" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label">Product <span class="text-danger">*</span></label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Choose a product…</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                data-type="{{ $product->product_type }}"
                                data-search="{{ strtolower($product->name.' '.($product->sku ?? '')) }}"
                                @selected((string) old('product_id') === (string) $product->id)>
                                {{ $product->name }}@if($product->sku) ({{ $product->sku }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3 d-none" id="variation-wrap">
                    <label class="form-label">Variation / size <span class="text-danger">*</span></label>
                    <select name="product_variation_id" id="product_variation_id" class="form-select">
                        <option value="">Select variation…</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Current stock</label>
                    <div id="current_stock_display" class="form-control bg-light">Select a product</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Channel <span class="text-danger">*</span></label>
                        <select name="stock_channel" id="stock_channel" class="form-select" required>
                            <option value="wholesale" @selected(old('stock_channel', 'wholesale') === 'wholesale')>Wholesale</option>
                            <option value="retail" @selected(old('stock_channel') === 'retail')>Retail (POS)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Action <span class="text-danger">*</span></label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="add" @selected(old('adjustment_type', 'add') === 'add')>Add (+)</option>
                            <option value="subtract" @selected(old('adjustment_type') === 'subtract')>Subtract (−)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason / notes <span class="text-danger">*</span></label>
                    <textarea name="notes" class="form-control" rows="3" required maxlength="500" placeholder="e.g. Found extra stock, damaged in transport…">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-warning" id="save-adjust-btn">
                    <i class="bi bi-check-circle me-1"></i> Save Adjustment
                </button>
            </form>
@endcanRoute

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var productSelect = document.getElementById('product_id');
    var variationWrap = document.getElementById('variation-wrap');
    var variationSelect = document.getElementById('product_variation_id');
    var stockDisplay = document.getElementById('current_stock_display');
    var channelSelect = document.getElementById('stock_channel');
    var filterInput = document.getElementById('product-filter');
    var productData = null;
    var dataUrl = @json(url('erp/products'));

    function filterProducts() {
        var q = (filterInput.value || '').toLowerCase().trim();
        Array.prototype.forEach.call(productSelect.options, function (opt) {
            if (!opt.value) return;
            var hay = opt.getAttribute('data-search') || opt.text.toLowerCase();
            opt.hidden = q !== '' && hay.indexOf(q) === -1;
        });
    }

    function availableForSelection() {
        if (!productData) return 0;
        var channel = channelSelect.value || 'wholesale';
        var vid = variationSelect.value;
        if (vid && productData.variations) {
            var v = productData.variations.find(function (row) { return String(row.id) === String(vid); });
            if (v) return channel === 'retail' ? Number(v.retail_stock || 0) : Number(v.wholesale_stock || 0);
        }
        return channel === 'retail' ? Number(productData.retail_stock || 0) : Number(productData.wholesale_stock || 0);
    }

    function renderStock() {
        if (!productData) {
            stockDisplay.textContent = 'Select a product';
            return;
        }
        var unit = productData.unit || 'pcs';
        var qty = availableForSelection();
        var extra = 'Wholesale ' + Number(productData.wholesale_stock || 0) + ' · Retail ' + Number(productData.retail_stock || 0);
        stockDisplay.innerHTML = '<strong>' + qty + '</strong> ' + unit + ' on selected channel &nbsp; <span class="text-muted small">(' + extra + ')</span>';
    }

    function loadProduct(id) {
        productData = null;
        variationSelect.innerHTML = '<option value="">Select variation…</option>';
        variationWrap.classList.add('d-none');
        variationSelect.required = false;
        if (!id) {
            renderStock();
            return;
        }
        stockDisplay.textContent = 'Loading…';
        fetch(dataUrl + '/' + id + '/stock-adjustment-data', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed');
                return res.json();
            })
            .then(function (data) {
                productData = data;
                if (data.product_type === 'variable' && Array.isArray(data.variations) && data.variations.length) {
                    variationWrap.classList.remove('d-none');
                    variationSelect.required = true;
                    data.variations.forEach(function (variation) {
                        var opt = document.createElement('option');
                        opt.value = variation.id;
                        opt.textContent = variation.name + ' (W ' + Number(variation.wholesale_stock || 0) + ' / R ' + Number(variation.retail_stock || 0) + ')';
                        variationSelect.appendChild(opt);
                    });
                }
                renderStock();
            })
            .catch(function () {
                stockDisplay.textContent = 'Could not load stock. Refresh and try again.';
            });
    }

    filterInput.addEventListener('input', filterProducts);
    productSelect.addEventListener('change', function () { loadProduct(this.value); });
    variationSelect.addEventListener('change', renderStock);
    channelSelect.addEventListener('change', renderStock);

    if (productSelect.value) {
        loadProduct(productSelect.value);
    }
})();
</script>
@endsection

@extends('erp.layout')

@section('content')
<div class="container-fluid" id="product-app">
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Add New Product</h1>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> Save Product
            </button>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <!-- Left Column: Main Form -->
            <div class="col-lg-9">
                
                <!-- Section 1: Basic Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg" placeholder="e.g. Cotton T-Shirt" required value="{{ old('name') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SKU <i class="bi bi-info-circle" title="Leave blank to auto-generate"></i></label>
                                <div class="input-group">
                                    <input type="text" name="sku" class="form-control" placeholder="Auto-generate" value="{{ old('sku') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Barcode Type</label>
                                <select name="barcode_type" class="form-select">
                                    <option value="C128">Code 128</option>
                                    <option value="EAN13">EAN-13</option>
                                    <option value="UPC">UPC</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <div class="input-group">
                                    <select name="category_id" class="form-select select2" id="category_select">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Brand</label>
                                <div class="input-group">
                                    <select name="brand_id" class="form-select select2" id="brand_select">
                                        <option value="">Select Brand</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addBrandModal"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="unit_id" class="form-select select2" id="unit_select" required>
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addUnitModal"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Product Type & Pricing -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Product Type & Pricing</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label d-block">Product Type</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="product_type" id="type_simple" value="simple" checked onchange="toggleVariations()">
                                <label class="form-check-label fw-bold" for="type_simple">Single Product</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="product_type" id="type_variable" value="variable" onchange="toggleVariations()">
                                <label class="form-check-label fw-bold" for="type_variable">Variable Product (Sizes/Colors)</label>
                            </div>
                        </div>

                        {{-- Simple product pricing --}}
                        <div id="pricing_section">
                            <div class="row p-3 bg-light rounded border mb-3">
                                <div class="col-md-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Excl. Tax Cost</label>
                                    <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control" oninput="calculateMargin()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Tax (%)</label>
                                    <input type="number" step="0.01" name="tax_rate" id="tax_rate" class="form-control" value="0" oninput="calculateMargin()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Profit Margin (%)</label>
                                    <input type="number" step="0.01" id="profit_margin" class="form-control" oninput="calculateSellingPrice()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Selling Price</label>
                                    <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control fw-bold text-success" oninput="calculateMarginFromSelling()">
                                </div>
                            </div>
                            <div class="row p-3 bg-light rounded border mb-0 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Wholesale Price (Rs) <small class="text-muted">— Sales person orders</small></label>
                                    <input type="number" step="0.01" name="wholesale_price" id="wholesale_price" class="form-control" value="{{ old('wholesale_price') }}" placeholder="Same as selling">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Retail Price (Rs) <small class="text-muted">— POS / counter sales</small></label>
                                    <input type="number" step="0.01" name="retail_price" id="retail_price" class="form-control" value="{{ old('retail_price') }}" placeholder="Same as selling">
                                </div>
                            </div>
                        </div>

                        {{-- Variable product note --}}
                        <div id="variable_price_note" class="d-none alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Variable product නම් පහළ <strong>Variations</strong> table එකේ variation එකකට price දාන්න.
                        </div>
                    </div>
                </div>

                <!-- Section 3: Inventory -->
                <div class="card shadow mb-4" id="inventory_section">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Inventory & Stock</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="enable_stock" id="enable_stock" checked onchange="toggleStockFields()">
                            <label class="form-check-label" for="enable_stock">Manage Stock?</label>
                        </div>

                        <div id="stock_fields">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Alert Quantity</label>
                                    <input type="number" name="alert_quantity" class="form-control" placeholder="Low stock alert at...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Opening Stock (Default Location)</label>
                                    <input type="number" name="opening_stock" class="form-control" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Variations (Dynamic) -->
                <div class="card shadow mb-4 d-none" id="variations_section">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Define Variations</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Define attributes (e.g. Size: S,M,L) to generate variations. Generate කරපු පස්සේ table එකේ variation එකකට price දාන්න.</p>

                        <!-- Step 1: Define Attributes -->
                        <div class="mb-4 p-3 bg-light border rounded">
                            <h6 class="fw-bold mb-3">1. Define Attributes</h6>
                            <div id="attribute_definitions"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addAttributeDefinition()">
                                <i class="bi bi-plus-lg"></i> Add Attribute
                            </button>
                        </div>

                        <!-- Step 2: Generate -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" onclick="generateVariationMatrix()">
                                <i class="bi bi-grid-3x3"></i> Generate Variations
                            </button>
                        </div>

                        <!-- Step 3: Matrix Table (no price / stock columns) -->
                        <div class="table-responsive" id="matrix_container" style="display:none;">
                            <div class="row g-2 mb-2" id="variation_price_defaults">
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Default Cost (Rs)</label>
                                    <input type="number" step="0.01" min="0" id="def_var_cost" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Default Selling (Rs)</label>
                                    <input type="number" step="0.01" min="0" id="def_var_sell" class="form-control form-control-sm" placeholder="0.00">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyDefaultVariationPrices()">Apply to all rows</button>
                                </div>
                            </div>
                            <table class="table table-bordered table-sm" id="variations_matrix">
                                <thead class="table-dark text-white">
                                    <tr>
                                        <th>Variation Name</th>
                                        <th>SKU Suffix</th>
                                        <th>Cost (Rs)</th>
                                        <th>Selling (Rs)</th>
                                        <th>Wholesale</th>
                                        <th>Retail</th>
                                        <th width="5%">Remove</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <p class="small text-muted mt-1"><i class="bi bi-info-circle"></i> Selling price එක අනිවාර්ය නොවේ — පසුව Edit Product වලින්ත් update කරන්න පුළුවන්.</p>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Image -->
                 <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Product Image</h6>
                    </div>
                    <div class="card-body">
                         <input type="file" name="image" class="form-control" accept="image/*">
                         <div class="form-text">JPG, PNG or WEBP. Max 2MB.</div>
                    </div>
                 </div>

            </div>

            <!-- Right Column: Sticky Summary -->
            <div class="col-lg-3">
                <div class="card shadow sticky-top" style="top: 20px; z-index: 100;">
                    <div class="card-header py-3 bg-gradient-primary text-white">
                        <h6 class="m-0 fw-bold">Summary Preview</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-bold">Expected Profit</small>
                            <h3 class="text-success fw-bold" id="preview_profit">0.00</h3>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-bold">Final Price</small>
                            <h3 class="text-dark fw-bold" id="preview_price">0.00</h3>
                        </div>
                        <hr>
                        <p class="small text-muted" id="preview_hint">
                            Check all tabs before saving. Variations will be generated automatically.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Inline Modals -->
@include('erp.products.partials.modals')

@endsection

@section('scripts')
<script>
    // --- Logic for Calculations ---
    function calculateMargin() {
        let cost = parseFloat(document.getElementById('cost_price').value) || 0;
        let tax = parseFloat(document.getElementById('tax_rate').value) || 0;
        let price = parseFloat(document.getElementById('selling_price').value) || 0;

        // If price is entered, calculate margin
        if (price > 0 && cost > 0) {
            let margin = ((price - cost) / cost) * 100;
            document.getElementById('profit_margin').value = margin.toFixed(2);
            updatePreview(cost, price);
        }
    }

    function calculateSellingPrice() {
        let cost = parseFloat(document.getElementById('cost_price').value) || 0;
        let margin = parseFloat(document.getElementById('profit_margin').value) || 0;
        
        if (cost > 0) {
            let profit = cost * (margin / 100);
            let price = cost + profit;
            document.getElementById('selling_price').value = price.toFixed(2);
            updatePreview(cost, price);
        }
    }

    function calculateMarginFromSelling() {
        // Same as calculateMargin, triggered when selling price changes
        calculateMargin();
    }

    function updatePreview(cost, price) {
        let profit = price - cost;
        document.getElementById('preview_profit').innerText = "LKR " + profit.toFixed(2);
        document.getElementById('preview_price').innerText = "LKR " + price.toFixed(2);
    }

    function getVariationRowPrice(row) {
        const sell = parseFloat(row.querySelector('.var-sell')?.value) || 0;
        const wholesale = parseFloat(row.querySelector('.var-wholesale')?.value) || 0;
        const retail = parseFloat(row.querySelector('.var-retail')?.value) || 0;
        return sell || wholesale || retail;
    }

    function updateVariationPreview() {
        const type = (document.querySelector('input[name="product_type"]:checked') || {}).value;
        if (type !== 'variable') {
            return;
        }

        const rows = document.querySelectorAll('#variations_matrix tbody tr');
        let totalPrice = 0;
        let totalProfit = 0;
        let priceCount = 0;
        let profitCount = 0;

        rows.forEach(row => {
            const cost = parseFloat(row.querySelector('.var-cost')?.value) || 0;
            const price = getVariationRowPrice(row);

            if (price > 0) {
                totalPrice += price;
                priceCount++;
            }

            if (price > 0 && cost > 0) {
                totalProfit += price - cost;
                profitCount++;
            }
        });

        const profitEl = document.getElementById('preview_profit');
        const priceEl = document.getElementById('preview_price');

        if (priceCount === 0) {
            profitEl.innerText = '0.00';
            priceEl.innerText = '0.00';
            return;
        }

        const avgPrice = totalPrice / priceCount;
        priceEl.innerText = 'LKR ' + avgPrice.toFixed(2) + (priceCount > 1 ? ' (avg)' : '');

        if (profitCount === 0) {
            profitEl.innerText = costMissingLabel(priceCount);
            profitEl.classList.remove('text-success');
            profitEl.classList.add('text-muted');
        } else {
            const avgProfit = totalProfit / profitCount;
            profitEl.innerText = 'LKR ' + avgProfit.toFixed(2) + (profitCount > 1 ? ' (avg)' : '');
            profitEl.classList.add('text-success');
            profitEl.classList.remove('text-muted');
        }
    }

    function costMissingLabel(priceCount) {
        return priceCount > 0 ? '— (Cost දාන්න)' : '0.00';
    }

    // --- Logic for Product Type Toggling ---
    function toggleVariations() {
        let type = (document.querySelector('input[name="product_type"]:checked') || {}).value;

        let varSection  = document.getElementById('variations_section');
        let invSection  = document.getElementById('inventory_section');
        let priceSection = document.getElementById('pricing_section');
        let varNote     = document.getElementById('variable_price_note');

        if (type === 'variable') {
            varSection?.classList.remove('d-none');
            invSection?.classList.add('d-none');
            priceSection?.classList.add('d-none');
            varNote?.classList.remove('d-none');
            document.getElementById('preview_hint').innerText = 'Variable product — Cost + Selling/Wholesale/Retail දානකොට profit auto calculate වෙනවා.';
            updateVariationPreview();
        } else {
            varSection?.classList.add('d-none');
            invSection?.classList.remove('d-none');
            priceSection?.classList.remove('d-none');
            varNote?.classList.add('d-none');
            document.getElementById('preview_hint').innerText = 'Check all tabs before saving.';
            calculateMargin();
        }
    }

    function toggleStockFields() {
        let enabled = document.getElementById('enable_stock').checked;
        let fields = document.getElementById('stock_fields');
        if (enabled) {
            if(fields) fields.classList.remove('d-none');
        } else {
            if(fields) fields.classList.add('d-none');
        }
    }

    // --- Advanced Variation Logic ---
    let attributeIndex = 0;

    function addAttributeDefinition() {
        let container = document.getElementById('attribute_definitions');
        let div = document.createElement('div');
        div.className = 'row mb-2 attribute-row';
        div.innerHTML = `
            <div class="col-md-4">
                <input type="text" class="form-control" name="attributes[${attributeIndex}][name]" placeholder="Attribute Name (e.g. Size)" required onchange="enableGenerate()">
            </div>
            <div class="col-md-7">
                <input type="text" class="form-control" name="attributes[${attributeIndex}][values]" placeholder="Values separated by comma (e.g. S, M, L)" required onchange="enableGenerate()">
                <small class="text-muted">Comma separated</small>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.row').remove()"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        attributeIndex++;
    }

    function enableGenerate() {
        // Optional: validation check
    }

    function applyDefaultVariationPrices() {
        const cost = document.getElementById('def_var_cost')?.value;
        const sell = document.getElementById('def_var_sell')?.value;
        document.querySelectorAll('.var-cost').forEach(el => { if (cost) el.value = cost; });
        document.querySelectorAll('.var-sell').forEach(el => { if (sell) el.value = sell; });
        document.querySelectorAll('.var-wholesale').forEach(el => { if (sell) el.value = sell; });
        document.querySelectorAll('.var-retail').forEach(el => { if (sell) el.value = sell; });
        updateVariationPreview();
    }

    function variationPriceCells(index) {
        return `
                <td><input type="number" step="0.01" min="0" name="variations[${index}][cost_price]" class="form-control form-control-sm var-cost" placeholder="0.00"></td>
                <td><input type="number" step="0.01" min="0" name="variations[${index}][selling_price]" class="form-control form-control-sm var-sell" placeholder="0.00"></td>
                <td><input type="number" step="0.01" min="0" name="variations[${index}][wholesale_price]" class="form-control form-control-sm var-wholesale" placeholder="0.00"></td>
                <td><input type="number" step="0.01" min="0" name="variations[${index}][retail_price]" class="form-control form-control-sm var-retail" placeholder="0.00"></td>
        `;
    }

    function generateVariationMatrix() {
        let attributes = [];
        document.querySelectorAll('.attribute-row').forEach(row => {
            let name = row.querySelector('input[name*="[name]"]').value.trim();
            let valuesStr = row.querySelector('input[name*="[values]"]').value;
            if (name && valuesStr) {
                let values = valuesStr.split(',').map(v => v.trim()).filter(v => v !== '');
                if (values.length > 0) attributes.push({ name, values });
            }
        });

        if (attributes.length === 0) {
            alert('Please define at least one attribute with values first.');
            return;
        }

        let cartesian = cartesianProduct(attributes.map(a => a.values));
        let tbody = document.querySelector('#variations_matrix tbody');
        tbody.innerHTML = '';
        document.getElementById('matrix_container').style.display = 'block';

        cartesian.forEach((combination, index) => {
            if (!Array.isArray(combination)) combination = [combination];
            let name = combination.join('-');
            let skuSuffix = combination.join('').toUpperCase().replace(/\s+/g, '');

            let row = tbody.insertRow();
            row.innerHTML = `
                <td>
                    <strong>${name}</strong>
                    <input type="hidden" name="variations[${index}][name]" value="${name}">
                </td>
                <td>
                    <input type="text" name="variations[${index}][sku]" class="form-control form-control-sm" value="${skuSuffix}" placeholder="SKU suffix">
                </td>
                ${variationPriceCells(index)}
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateVariationPreview();"><i class="bi bi-x"></i></button>
                </td>
            `;
        });

        updateVariationPreview();
    }

    function cartesianProduct(arrays) {
        return arrays.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
    }

    // Initialize with one attribute row
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('.select2').select2({ width: '85%' });
        }
        
        // Ensure UI state matches selected radio button on load
        toggleVariations();

        const matrix = document.getElementById('variations_matrix');
        if (matrix) {
            matrix.addEventListener('input', function(e) {
                if (e.target.matches('.var-cost, .var-sell, .var-wholesale, .var-retail')) {
                    updateVariationPreview();
                }
            });
        }

        ['def_var_cost', 'def_var_sell'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', updateVariationPreview);
        });
    });

    // --- Inline Modal Submission Logic can be added here ---
    function saveInline(type) {
        // Implementation for Modals
        // Fetch POST to relevant store route
        // On success, append option to select and close modal
    }
</script>
@endsection

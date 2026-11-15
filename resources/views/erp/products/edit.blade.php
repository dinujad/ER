@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Product: {{ $product->name }}</h1>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
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

    @if(!empty($manufacturingCost))
    <div class="alert alert-success border-success mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong><i class="bi bi-gear-wide-connected me-1"></i> Manufacturing cost (auto)</strong>
                <span class="ms-2 fs-5 fw-bold">Rs {{ number_format($manufacturingCost['cost_per_unit'], 2) }}</span>
                <span class="text-muted">/ pc</span>
                <small class="d-block text-muted mt-1">
                    Job {{ $manufacturingCost['job_number'] }}
                    @if($manufacturingCost['finalized'])
                        — stock restocked + cost applied on convert
                    @else
                        — estimate (convert කළාම product/variation cost update වෙනවා)
                    @endif
                </small>
            </div>
            @if(auth()->user()?->can_access('manufacturing.costs'))
            <a href="{{ route('jobs.costs', $manufacturingCost['job_id']) }}" class="btn btn-sm btn-outline-success">View job cost</a>
            @endif
        </div>
    </div>
    @endif

    @canRoute('products.update')
<form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- Left Column: Basic Info -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Product Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU (Unique)</label>
                                <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Barcode (Scan/Type)</label>
                                <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Product Type</label>
                                <input type="text" class="form-control" value="{{ ucfirst($product->product_type) }}" disabled>
                                <small class="text-muted">Cannot be changed after creation</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Pricing</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Cost Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price > 0 ? $product->cost_price : ($manufacturingCost['cost_per_unit'] ?? $product->cost_price)) }}" required>
                                @if($product->product_type === 'variable')
                                    <small class="text-muted">Manufacturing convert වෙද්දී auto-fill. Variation-wise cost පහළ table එකේ.</small>
                                @elseif(!empty($manufacturingCost) && (float) $product->cost_price <= 0)
                                    <small class="text-success">Manufacturing job cost auto suggested above.</small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price (LKR) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="selling_price" class="form-control" value="{{ old('selling_price', $product->selling_price) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="{{ old('tax_rate', $product->tax_rate) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Wholesale Price (Rs) <small class="text-muted">— Sales person orders</small></label>
                                <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $product->wholesale_price ?? $product->selling_price) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Retail Price (Rs) <small class="text-muted">— POS sales</small></label>
                                <input type="number" step="0.01" name="retail_price" class="form-control" value="{{ old('retail_price', $product->retail_price ?? $product->selling_price) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Taxonomy -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Organization</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ $product->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->short_name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warranty</label>
                            <select name="warranty_id" class="form-select">
                                <option value="">Select Warranty</option>
                                @foreach($warranties as $war)
                                    <option value="{{ $war->id }}" {{ $product->warranty_id == $war->id ? 'selected' : '' }}>{{ $war->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ $product->is_active ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !$product->is_active ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="bi bi-save"></i> Update Product
                </button>
            </div>
        </div>
    </form>
@endcanRoute

    <!-- Variations Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Product Variations
                        @if($product->product_type === 'variable')
                            <span class="badge bg-primary ms-2">Variable Product</span>
                        @else
                            <span class="badge bg-secondary ms-2">Simple — adding a variation converts to Variable</span>
                        @endif
                    </h6>
                </div>
                <div class="card-body">

                    {{-- Existing variations with inline price editing --}}
                    @if($product->variations->count() > 0)
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Variation</th>
                                    <th>SKU</th>
                                    <th>Stock</th>
                                    <th>Cost</th>
                                    <th>Sell</th>
                                    <th>Wholesale</th>
                                    <th>Retail</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->variations as $variation)
                                @php $unpriced = (float) $variation->default_sell_price <= 0; @endphp
                                <tr class="{{ $unpriced ? 'table-warning' : '' }}">
                                    <td class="fw-semibold">
                                        {{ $variation->variation_name }}
                                        @if($unpriced)<span class="badge bg-warning text-dark ms-1">No price</span>@endif
                                    </td>
                                    <td class="small text-muted">{{ $variation->sub_sku ?? '—' }}</td>
                                    <td>{{ $variation->current_stock }}</td>
                                <td>{{ $variation->default_purchase_price > 0 ? number_format($variation->default_purchase_price, 2) : (!empty($manufacturingCost) ? number_format($manufacturingCost['cost_per_unit'], 2) . ' (mfg)' : '—') }}</td>
                                    <td>{{ $variation->default_sell_price > 0 ? number_format($variation->default_sell_price, 2) : '—' }}</td>
                                    <td>{{ $variation->wholesale_price > 0 ? number_format($variation->wholesale_price, 2) : '—' }}</td>
                                    <td>{{ $variation->retail_price > 0 ? number_format($variation->retail_price, 2) : '—' }}</td>
                                    <td class="text-center text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#priceModal{{ $variation->id }}">
                                            <i class="bi bi-tag"></i> Set Price
                                        </button>
                                        @canRoute('products.variations.destroy')
<form action="{{ route('products.variations.destroy', [$product->id, $variation->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete variation?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
@endcanRoute
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Price modals --}}
                    @foreach($product->variations as $variation)
                    <div class="modal fade" id="priceModal{{ $variation->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                @canRoute('products.variations.update-prices')
<form method="POST" action="{{ route('products.variations.update-prices', [$product->id, $variation->id]) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Set Prices — {{ $variation->variation_name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label">Cost Price</label>
                                                <input type="number" step="0.01" name="default_purchase_price" class="form-control" value="{{ old('default_purchase_price', $variation->default_purchase_price > 0 ? $variation->default_purchase_price : '') }}" placeholder="0.00">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" name="default_sell_price" class="form-control" value="{{ old('default_sell_price', $variation->default_sell_price > 0 ? $variation->default_sell_price : '') }}" placeholder="0.00" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Wholesale Price <small class="text-muted">— sales person</small></label>
                                                <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $variation->wholesale_price > 0 ? $variation->wholesale_price : '') }}" placeholder="Same as selling">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Retail Price <small class="text-muted">— POS</small></label>
                                                <input type="number" step="0.01" name="retail_price" class="form-control" value="{{ old('retail_price', $variation->retail_price > 0 ? $variation->retail_price : '') }}" placeholder="Same as selling">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Prices</button>
                                    </div>
                                </form>
@endcanRoute
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <p class="text-muted">No variations yet. Use the generator below to add variations.</p>
                    @endif

                    {{-- Add Variations via Generator --}}
                    <div class="card bg-light border-0 mt-3">
                        <div class="card-body">
                            <h6 class="card-title fw-bold">Add Variations via Attribute Generator</h6>
                            <p class="small text-muted">Define attributes (e.g. Size: S,M,L × Color: Red,Blue). Generate කරපු පස්සේ table එකේ price දාන්න.</p>

                            <div class="mb-3 p-3 bg-white border rounded">
                                <label class="fw-bold mb-2">Define Attributes</label>
                                <div id="attribute_definitions"></div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addAttributeDefinition()">
                                    <i class="bi bi-plus-lg"></i> Add Attribute
                                </button>
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" onclick="generateVariationMatrix()">
                                    <i class="bi bi-grid-3x3"></i> Generate Variations
                                </button>
                            </div>

                            <form action="{{ route('products.variations.store_many', $product->id) }}" method="POST" id="variation_gen_form" style="display:none;">
                                @csrf
                                <div class="row g-2 mb-2">
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
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm bg-white" id="variations_matrix">
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
                                    <p class="small text-muted mt-1"><i class="bi bi-info-circle"></i> Variation එකකට price මෙතනින්ම දාන්න පුළුවන්.</p>
                                </div>
                                <button type="submit" class="btn btn-success mt-3">
                                    <i class="bi bi-save"></i> Save All Variations
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    let attributeIndex = 0;

    function applyDefaultVariationPrices() {
        const cost = document.getElementById('def_var_cost')?.value;
        const sell = document.getElementById('def_var_sell')?.value;
        document.querySelectorAll('.var-cost').forEach(el => { if (cost) el.value = cost; });
        document.querySelectorAll('.var-sell').forEach(el => { if (sell) el.value = sell; });
        document.querySelectorAll('.var-wholesale').forEach(el => { if (sell) el.value = sell; });
        document.querySelectorAll('.var-retail').forEach(el => { if (sell) el.value = sell; });
    }

    function variationPriceCells(index) {
        return `
                <td><input type="number" step="0.01" min="0" name="variations[${index}][cost_price]" class="form-control form-control-sm var-cost" placeholder="0.00"></td>
                <td><input type="number" step="0.01" min="0" name="variations[${index}][selling_price]" class="form-control form-control-sm var-sell" placeholder="0.00"></td>
                <td><input type="number" step="0.01" min="0" name="variations[${index}][wholesale_price]" class="form-control form-control-sm var-wholesale" placeholder="0.00"></td>
                <td><input type="number" step="0.01" min="0" name="variations[${index}][retail_price]" class="form-control form-control-sm var-retail" placeholder="0.00"></td>
        `;
    }

    function addAttributeDefinition() {
        let container = document.getElementById('attribute_definitions');
        let div = document.createElement('div');
        div.className = 'row mb-2 attribute-row';
        div.innerHTML = `
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="attributes[${attributeIndex}][name]" placeholder="Name (e.g. Size)" required>
            </div>
            <div class="col-md-7">
                <input type="text" class="form-control form-control-sm" name="attributes[${attributeIndex}][values]" placeholder="Values (e.g. S, M, L)" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.row').remove()"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        attributeIndex++;
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
        document.getElementById('variation_gen_form').style.display = 'block';

        let productSku = "{{ $product->sku }}";

        cartesian.forEach((combination, index) => {
            if (!Array.isArray(combination)) combination = [combination];
            let name = combination.join('-');
            let skuSuffix = combination.join('').toUpperCase().replace(/\s+/g, '');

            let row = tbody.insertRow();
            row.innerHTML = `
                <td>
                    <strong>${name}</strong>
                    <input type="hidden" name="variations[${index}][variation_name]" value="${name}">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">${productSku}-</span>
                        <input type="text" name="variations[${index}][sku_suffix]" class="form-control" value="${skuSuffix}">
                    </div>
                </td>
                ${variationPriceCells(index)}
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button>
                </td>
            `;
        });
    }

    function cartesianProduct(arrays) {
        return arrays.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
    }

    // Initialize with one row
    document.addEventListener('DOMContentLoaded', function() {
        addAttributeDefinition();
    });
</script>
@endsection


@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
        <div>
            <a href="{{ route('products.import.create') }}" class="btn btn-secondary me-2">
                <i class="bi bi-upload"></i> Import CSV
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Product
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'wholesale' ? 'active fw-bold' : '' }}"
               href="{{ route('products.index', array_merge(request()->except('page'), ['tab' => 'wholesale'])) }}">
                <i class="bi bi-building me-1"></i> Wholesale
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'retail' ? 'active fw-bold' : '' }}"
               href="{{ route('products.index', array_merge(request()->except('page'), ['tab' => 'retail'])) }}">
                <i class="bi bi-shop me-1"></i> Retail
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}"
               href="{{ route('products.index', array_merge(request()->except('page'), ['tab' => 'all'])) }}">
                All Products
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'gproducts' ? 'active fw-bold' : '' }}"
               href="{{ route('products.index', ['tab' => 'gproducts']) }}">
                <i class="bi bi-inbox me-1"></i> Gproducts (Draft)
                @if(($gproductsCount ?? 0) > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $gproductsCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'dproducts' ? 'active' : '' }}"
               href="{{ route('products.index', ['tab' => 'dproducts']) }}">
                Dproducts (Damage)
            </a>
        </li>
    </ul>

    @if($tab === 'gproducts')
        <div class="alert alert-warning py-2 small mb-3">
            <strong>Final Checking එකෙන් convert කළ products මෙතැන.</strong>
            Variations manufacturing එකෙන් එනවා + <strong>cost auto</strong>.
            Admin selling / wholesale / retail prices set කරලා <strong>Publish</strong> කරන්න.
        </div>
    @elseif($tab === 'wholesale')
        <div class="alert alert-primary py-2 small mb-3">
            <strong>Wholesale stock</strong> — manufacturing, wholesale orders.
            Retail එකට යවන්න <strong>→ Retail</strong>, retail එකෙන් ආපහු ගන්න Retail tab එකේ <strong>→ Wholesale</strong>.
            @if(($gproductsCount ?? 0) > 0)
                <span class="text-warning"> · {{ $gproductsCount }} draft product(s) waiting in <a href="{{ route('products.index', ['tab' => 'gproducts']) }}">Gproducts</a>.</span>
            @endif
        </div>
    @elseif($tab === 'retail')
        <div class="alert alert-info py-2 small mb-3">
            <strong>Retail stock</strong> — POS / walk-in sales.
            Wholesale එකට ආපහු යවන්න <strong>→ Wholesale</strong> button එකෙන්. Wholesale එකෙන් shop එකට ගන්න Wholesale tab එකේ <strong>→ Retail</strong>.
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form action="{{ route('products.index') }}" method="GET" class="row g-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search product name, SKU..." value="{{ request('search') }}">
                </div>
                <!-- Add more filters here later -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Search</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Cost</th>
                            <th>{{ $tab === 'retail' ? 'Retail Price' : ($tab === 'wholesale' ? 'Wholesale Price' : 'Selling Price') }}</th>
                            <th class="{{ $tab === 'retail' ? 'text-muted' : '' }}">Wholesale Stock</th>
                            <th class="{{ $tab === 'wholesale' ? 'text-muted' : '' }}">Retail Stock</th>
                            @if(!in_array($tab, ['wholesale', 'retail'], true))
                            <th>Total</th>
                            @endif
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" width="50" alt="{{ $product->name }}">
                                    @else
                                        <span class="badge bg-secondary">No Img</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $product->name }}</div>
                                    <small class="text-muted">{{ $product->product_type }}</small>
                                    @if(($product->status ?? '') === 'draft')
                                        <span class="badge bg-warning text-dark ms-1">Draft</span>
                                    @endif
                                    @if(($product->classification ?? '') === 'damage')
                                        <span class="badge bg-danger ms-1">Damage</span>
                                    @endif
                                </td>
                                <td>{{ $product->sku ?? '-' }}</td>
                                <td>{{ $product->category->name ?? '-' }}</td>
                                <td>{{ $product->brand->name ?? '-' }}</td>
                                <td>
                                    @php $displayCost = $product->displayCostPrice(); @endphp
                                    @if($displayCost > 0)
                                        {{ number_format($displayCost, 2) }}
                                        @if($product->product_type === 'variable')
                                            <small class="text-muted d-block">per variation</small>
                                        @endif
                                    @elseif($meta = $product->latestManufacturingCostMeta())
                                        <span class="text-success">{{ number_format($meta['cost_per_unit'], 2) }}</span>
                                        <small class="text-muted d-block">mfg auto</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tab === 'retail')
                                        {{ number_format($product->retail_price ?? $product->selling_price, 2) }}
                                    @elseif($tab === 'wholesale')
                                        {{ number_format($product->wholesale_price ?? $product->selling_price, 2) }}
                                    @else
                                        {{ number_format($product->selling_price, 2) }}
                                    @endif
                                </td>
                                <td class="{{ $tab === 'retail' ? 'opacity-50' : '' }}">
                                    <span class="badge {{ $tab === 'wholesale' ? 'bg-primary fs-6' : 'bg-primary' }}">
                                        {{ number_format($product->wholesale_stock, 0) }}
                                    </span>
                                </td>
                                <td class="{{ $tab === 'wholesale' ? 'opacity-50' : '' }}">
                                    <span class="badge {{ $tab === 'retail' ? 'bg-info text-dark fs-6' : 'bg-info text-dark' }}">
                                        {{ number_format($product->retail_stock, 0) }}
                                    </span>
                                </td>
                                @if(!in_array($tab, ['wholesale', 'retail'], true))
                                <td>
                                    <span class="badge {{ $product->current_stock <= $product->alert_quantity ? 'bg-danger' : 'bg-success' }}">
                                        {{ number_format($product->current_stock, 0) }} {{ $product->unit?->short_name ?? '' }}
                                    </span>
                                </td>
                                @endif
                                <td>
                                    @if($tab !== 'gproducts' && $tab !== 'dproducts')
                                    <button type="button" class="btn btn-sm btn-outline-warning channel-transfer-btn"
                                            title="Wholesale → Retail"
                                            data-bs-toggle="modal" data-bs-target="#channelTransferModal"
                                            data-direction="wholesale_to_retail"
                                            data-product-id="{{ $product->id }}"
                                            data-product-name="{{ $product->name }}"
                                            data-wholesale-stock="{{ number_format((float)$product->wholesale_stock, 2, '.', '') }}"
                                            data-retail-stock="{{ number_format((float)$product->retail_stock, 2, '.', '') }}">
                                        → Retail
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info channel-transfer-btn"
                                            title="Retail → Wholesale"
                                            data-bs-toggle="modal" data-bs-target="#channelTransferModal"
                                            data-direction="retail_to_wholesale"
                                            data-product-id="{{ $product->id }}"
                                            data-product-name="{{ $product->name }}"
                                            data-wholesale-stock="{{ number_format((float)$product->wholesale_stock, 2, '.', '') }}"
                                            data-retail-stock="{{ number_format((float)$product->retail_stock, 2, '.', '') }}">
                                        → Wholesale
                                    </button>
                                    @endif
                                    <a href="{{ route('products.print-barcode', $product->id) }}" class="btn btn-sm btn-dark" title="Print Labels"><i class="bi bi-upc-scan"></i></a>
                                    <a href="{{ route('products.history', $product->id) }}" class="btn btn-sm btn-secondary" title="View History"><i class="bi bi-clock-history"></i></a>
                                    @canRoute('stock-adjustment.store')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning stock-adjust-btn"
                                        title="Add / Edit Stock"
                                        data-bs-toggle="modal"
                                        data-bs-target="#stockAdjustModal"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        data-current-stock="{{ number_format((float)$product->current_stock, 2, '.', '') }}"
                                    >
                                        <i class="bi bi-box-seam"></i>
                                    </button>
                                    @endcanRoute
                                    @canRoute('products.edit')
<a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-info" title="Edit"><i class="bi bi-pencil"></i></a>
@endcanRoute
                                    @if($tab === 'gproducts' && ($product->status ?? '') === 'draft' && ($product->classification ?? 'standard') === 'standard')
                                    <form action="{{ route('products.publish', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Publish this product? It will appear in Wholesale/Retail lists.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Publish to Wholesale/Retail">
                                            <i class="bi bi-check-circle"></i> Publish
                                        </button>
                                    </form>
                                    @endif
                                    @canRoute('products.destroy')
<form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
@endcanRoute
                                </td>
                            </tr>
                            @if($tab === 'gproducts' && $product->product_type === 'variable' && $product->variations->count() > 0)
                            <tr class="bg-light">
                                <td></td>
                                <td colspan="{{ in_array($tab, ['wholesale', 'retail'], true) ? 9 : 10 }}">
                                    <div class="small ps-2">
                                        <strong class="text-primary"><i class="bi bi-grid-3x3-gap me-1"></i>Manufacturing variations:</strong>
                                        <table class="table table-sm table-bordered mb-0 mt-1 bg-white" style="max-width:720px">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Variation</th>
                                                    <th>SKU</th>
                                                    <th class="text-end">Cost</th>
                                                    <th class="text-end">Sell</th>
                                                    <th class="text-end">Stock</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($product->variations as $variation)
                                                <tr>
                                                    <td>{{ $variation->variation_name }}</td>
                                                    <td class="text-muted">{{ $variation->sub_sku }}</td>
                                                    <td class="text-end">{{ $variation->default_purchase_price > 0 ? number_format($variation->default_purchase_price, 2) : '—' }}</td>
                                                    <td class="text-end">{{ $variation->default_sell_price > 0 ? number_format($variation->default_sell_price, 2) : '—' }}</td>
                                                    <td class="text-end"><span class="badge bg-primary">{{ number_format($variation->current_stock, 0) }}</span></td>
                                                    <td class="text-end">
                                                        <a href="{{ route('products.print-barcode', ['product' => $product->id, 'variation' => $variation->id]) }}" class="btn btn-sm btn-dark py-0" title="Print this variation barcode"><i class="bi bi-upc-scan"></i></a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ in_array($tab, ['wholesale', 'retail'], true) ? 10 : 11 }}" class="text-center py-4">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-labelledby="stockAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @canRoute('stock-adjustment.store')
<form method="POST" action="{{ route('stock-adjustment.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="stockAdjustModalLabel">Add / Edit Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="stock-adjust-product-id">
                    <input type="hidden" name="product_variation_id" id="stock-adjust-variation-id">

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-0">Product</label>
                        <div id="stock-adjust-product-name" class="text-muted"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold mb-0">Current Stock</label>
                        <div id="stock-adjust-current-stock" class="text-success"></div>
                    </div>
                    <div class="mb-3 d-none" id="variation-select-wrap">
                        <label class="form-label">Variation</label>
                        <select class="form-select" id="stock-adjust-variation-select">
                            <option value="">Select variation</option>
                        </select>
                        <small class="text-muted">Variable product නම් variation wise stock update කරන්න.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stock channel</label>
                        <select name="stock_channel" class="form-select" required id="stock-adjust-channel">
                            <option value="wholesale" @selected(($stockChannel ?? '') === 'wholesale')>Wholesale (manufacturing / orders)</option>
                            <option value="retail" @selected(($stockChannel ?? '') === 'retail')>Retail only (POS — cannot move back to wholesale)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="add">Add Stock</option>
                            <option value="subtract">Subtract Stock</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Reason for stock update" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
@endcanRoute

        </div>
    </div>
</div>

<div class="modal fade" id="channelTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="channel-transfer-form" action="">
                @csrf
                <input type="hidden" name="direction" id="channel-transfer-direction" value="wholesale_to_retail">
                <div class="modal-header">
                    <h5 class="modal-title" id="channel-transfer-title">Wholesale → Retail Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2" id="channel-transfer-help">Wholesale stock retail shop එකට යවන්න.</p>
                    <div class="mb-2"><strong id="channel-transfer-product-name"></strong></div>
                    <div class="mb-3 d-none" id="channel-transfer-variation-wrap">
                        <label class="form-label">Variation <span class="text-danger">*</span></label>
                        <select class="form-select" id="channel-transfer-variation-select" required disabled>
                            <option value="">Loading…</option>
                        </select>
                        <small class="text-muted">Variable product — stock variation එකට තියෙනවා.</small>
                    </div>
                    <div class="mb-3">Available <span id="channel-transfer-from-label">wholesale</span>: <span class="badge bg-primary fs-6" id="channel-transfer-available-qty">0</span></div>
                    <input type="hidden" name="product_variation_id" id="channel-transfer-variation-id" value="">
                    <div class="mb-3">
                        <label class="form-label">Quantity to move</label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" required id="channel-transfer-qty">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes (optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Sent to shop floor / returned to warehouse">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="channel-transfer-submit">Transfer to Retail</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('product_delete_error'))
<div class="modal fade" id="productDeleteErrorModal" tabindex="-1" aria-labelledby="productDeleteErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="productDeleteErrorModalLabel">Delete කරන්න බැහැ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ session('product_delete_error') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.stock-adjust-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const productId = this.dataset.productId;
                const productNameEl = document.getElementById('stock-adjust-product-name');
                const currentStockEl = document.getElementById('stock-adjust-current-stock');
                const variationWrap = document.getElementById('variation-select-wrap');
                const variationSelect = document.getElementById('stock-adjust-variation-select');
                const variationIdInput = document.getElementById('stock-adjust-variation-id');

                document.getElementById('stock-adjust-product-id').value = productId;
                variationIdInput.value = '';
                variationSelect.innerHTML = '<option value="">Select variation</option>';
                productNameEl.textContent = this.dataset.productName || '-';
                currentStockEl.textContent = (this.dataset.currentStock || '0.00') + ' units';
                variationWrap.classList.add('d-none');

                try {
                    const response = await fetch(`{{ url('erp/products') }}/${productId}/stock-adjustment-data`);
                    if (!response.ok) throw new Error('Failed');
                    const data = await response.json();

                    productNameEl.textContent = data.name || this.dataset.productName || '-';
                    currentStockEl.innerHTML = 'Wholesale: <strong>' + (data.wholesale_stock ?? '0') + '</strong> | Retail: <strong>' + (data.retail_stock ?? '0') + '</strong> | Total: ' + (data.current_stock ?? '0') + ' ' + (data.unit || 'units');

                    if (data.product_type === 'variable' && Array.isArray(data.variations) && data.variations.length) {
                        variationWrap.classList.remove('d-none');
                        data.variations.forEach(function (variation) {
                            const option = document.createElement('option');
                            option.value = variation.id;
                            option.textContent = `${variation.name} (Stock: ${variation.current_stock})`;
                            option.setAttribute('data-stock', variation.current_stock);
                            variationSelect.appendChild(option);
                        });
                    }
                } catch (e) {
                    console.error(e);
                }
            });
        });

        const variationSelect = document.getElementById('stock-adjust-variation-select');
        if (variationSelect) {
            variationSelect.addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                const variationIdInput = document.getElementById('stock-adjust-variation-id');
                const currentStockEl = document.getElementById('stock-adjust-current-stock');
                variationIdInput.value = this.value || '';
                if (this.value) {
                    const stock = selected.getAttribute('data-stock') || '0.00';
                    currentStockEl.textContent = stock + ' units';
                }
            });
        }

        const transferVariationWrap = document.getElementById('channel-transfer-variation-wrap');
        const transferVariationSelect = document.getElementById('channel-transfer-variation-select');
        const transferVariationIdInput = document.getElementById('channel-transfer-variation-id');
        const transferAvailableQty = document.getElementById('channel-transfer-available-qty');
        const transferDirectionInput = document.getElementById('channel-transfer-direction');
        const transferFromLabel = document.getElementById('channel-transfer-from-label');
        const transferTitle = document.getElementById('channel-transfer-title');
        const transferHelp = document.getElementById('channel-transfer-help');
        const transferSubmit = document.getElementById('channel-transfer-submit');
        let transferDirection = 'wholesale_to_retail';

        function isToRetail() {
            return transferDirection === 'wholesale_to_retail';
        }

        function setTransferAvailable(qty) {
            transferAvailableQty.textContent = qty;
            const qtyInput = document.getElementById('channel-transfer-qty');
            if (qtyInput) {
                qtyInput.max = parseFloat(qty) > 0 ? qty : '';
            }
        }

        function applyTransferDirection(direction) {
            transferDirection = direction === 'retail_to_wholesale' ? 'retail_to_wholesale' : 'wholesale_to_retail';
            transferDirectionInput.value = transferDirection;
            if (isToRetail()) {
                transferTitle.textContent = 'Wholesale → Retail Transfer';
                transferHelp.textContent = 'Wholesale stock retail shop එකට යවන්න.';
                transferFromLabel.textContent = 'wholesale';
                transferSubmit.textContent = 'Transfer to Retail';
                transferSubmit.className = 'btn btn-warning';
                transferAvailableQty.className = 'badge bg-primary fs-6';
            } else {
                transferTitle.textContent = 'Retail → Wholesale Transfer';
                transferHelp.textContent = 'Retail stock warehouse / wholesale එකට ආපහු යවන්න.';
                transferFromLabel.textContent = 'retail';
                transferSubmit.textContent = 'Transfer to Wholesale';
                transferSubmit.className = 'btn btn-info';
                transferAvailableQty.className = 'badge bg-info text-dark fs-6';
            }
        }

        document.querySelectorAll('.channel-transfer-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const productId = this.dataset.productId;
                applyTransferDirection(this.dataset.direction || 'wholesale_to_retail');
                document.getElementById('channel-transfer-form').action =
                    '{{ url('erp/products') }}/' + productId + '/transfer-channel-stock';
                document.getElementById('channel-transfer-product-name').textContent = this.dataset.productName || '';
                transferVariationIdInput.value = '';
                transferVariationWrap.classList.add('d-none');
                transferVariationSelect.innerHTML = '<option value="">Loading…</option>';
                transferVariationSelect.removeAttribute('name');
                transferVariationSelect.disabled = true;
                setTransferAvailable(
                    isToRetail()
                        ? (this.dataset.wholesaleStock || '0')
                        : (this.dataset.retailStock || '0')
                );

                try {
                    const response = await fetch(`{{ url('erp/products') }}/${productId}/stock-adjustment-data`);
                    if (!response.ok) throw new Error('Failed');
                    const data = await response.json();

                    if (data.product_type === 'variable' && data.variations && data.variations.length) {
                        transferVariationWrap.classList.remove('d-none');
                        transferVariationSelect.innerHTML = '<option value="">-- Select variation --</option>';
                        transferVariationSelect.setAttribute('name', 'product_variation_id');
                        transferVariationSelect.disabled = false;
                        transferVariationSelect.required = true;
                        transferVariationIdInput.removeAttribute('name');

                        data.variations.forEach(function (v) {
                            const opt = document.createElement('option');
                            opt.value = v.id;
                            const fromQty = isToRetail() ? (v.wholesale_stock ?? 0) : (v.retail_stock ?? 0);
                            const fromLabel = isToRetail() ? 'Wholesale' : 'Retail';
                            opt.textContent = v.name + ' (' + fromLabel + ': ' + fromQty + ')';
                            opt.setAttribute('data-from-qty', fromQty);
                            transferVariationSelect.appendChild(opt);
                        });
                        setTransferAvailable('0');
                    } else {
                        transferVariationSelect.removeAttribute('name');
                        transferVariationSelect.required = false;
                        transferVariationIdInput.setAttribute('name', 'product_variation_id');
                        setTransferAvailable(
                            isToRetail()
                                ? (data.wholesale_stock ?? this.dataset.wholesaleStock ?? '0')
                                : (data.retail_stock ?? this.dataset.retailStock ?? '0')
                        );
                    }
                } catch (e) {
                    console.error(e);
                    setTransferAvailable(
                        isToRetail()
                            ? (this.dataset.wholesaleStock || '0')
                            : (this.dataset.retailStock || '0')
                    );
                }
            });
        });

        if (transferVariationSelect) {
            transferVariationSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                transferVariationIdInput.value = this.value || '';
                if (this.value) {
                    setTransferAvailable(opt.getAttribute('data-from-qty') || '0');
                } else {
                    setTransferAvailable('0');
                }
            });
        }

        @if(session('product_delete_error'))
        const deleteErrorModal = new bootstrap.Modal(document.getElementById('productDeleteErrorModal'));
        deleteErrorModal.show();
        @endif
    });
</script>
@endsection

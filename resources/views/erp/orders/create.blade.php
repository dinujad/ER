@extends('erp.layout')

@section('title', 'Create Order')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Create New Order</h1>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Order Details</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('orders.store') }}">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 mb-1">
                            <select class="form-control select2 @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                <option value="">-- Select Customer --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->display_name }}@if($customer->phone) ({{ $customer->phone }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('erp.customers.create', ['type' => 'wholesale']) }}" class="btn btn-outline-primary text-nowrap" title="Add new customer">
                                <i class="bi bi-person-plus"></i>
                            </a>
                        </div>
                        <small class="text-muted">List shows <strong>business name</strong> first. Add customer if missing.</small>
                    </div>
                    <div class="col-md-4">
                        <label for="sales_person_id" class="form-label">Sales Person</label>
                        <select class="form-control select2 @error('sales_person_id') is-invalid @enderror" id="sales_person_id" name="sales_person_id">
                            <option value="">-- Select Sales Person --</option>
                            @foreach($salesPersons as $sp)
                                <option value="{{ $sp->id }}" {{ old('sales_person_id') == $sp->id ? 'selected' : '' }}>
                                    {{ $sp->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Optional — leave empty if admin places the order.</small>
                    </div>
                    <div class="col-md-4">
                        <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('order_date') is-invalid @enderror" id="order_date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Add Products</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="product_select" class="form-label">Select Product</label>
                        <select class="form-control" id="product_select">
                            <option value="">-- Select Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="variations_container" class="mb-4" style="display: none;">
                    <h6>Available Variations</h6>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Variation/Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="variations_table_body">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <h6>Order Items</h6>
                    <table class="table table-striped" id="order_items_table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="order_items_body">
                            <!-- Selected items will be moved here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end" style="text-align: right;">Total Amount:</th>
                                <th id="grand_total">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label fw-semibold">Note</label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="2000" placeholder="Optional note for this order">{{ old('notes') }}</textarea>
                    <div class="form-text">Shown on this order page only. It is not printed on the order form.</div>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary me-md-2" id="submit_order_btn" disabled>Place Order</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productSelect = document.getElementById('product_select');
        const variationsContainer = document.getElementById('variations_container');
        const variationsTableBody = document.getElementById('variations_table_body');
        const orderItemsBody = document.getElementById('order_items_body');
        const grandTotalEl = document.getElementById('grand_total');
        const submitBtn = document.getElementById('submit_order_btn');
        let orderTotal = 0;
        let itemCount = 0;

        productSelect.addEventListener('change', function() {
            const productId = this.value;
            if (!productId) {
                variationsContainer.style.display = 'none';
                return;
            }

            variationsTableBody.innerHTML = '<tr><td colspan="4" class="text-muted">Loading variations…</td></tr>';
            variationsContainer.style.display = 'block';

            fetch(`{{ route('products.get_variations', ['product' => '__PID__']) }}`.replace('__PID__', productId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(response => {
                     if (!response.ok) {
                         return response.text().then(t => { throw new Error(t || ('HTTP ' + response.status)); });
                     }
                     return response.json();
                })
                .then(data => {
                    variationsTableBody.innerHTML = '';

                    if (data.type === 'variable' && Array.isArray(data.variations) && data.variations.length) {
                        data.variations.forEach(variation => {
                            const displayName = `${data.product_name || ''} — ${variation.variation_name}`.trim();
                            const row = `
                                <tr>
                                    <td>${displayName}</td>
                                    <td>${Number(variation.default_sell_price).toFixed(2)}</td>
                                    <td>
                                        <input type="number" min="1" class="form-control form-control-sm quantity-input" id="qty_var_${variation.id}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success add-item-btn" 
                                            data-product-id="${variation.product_id}" 
                                            data-variation-id="${variation.id}"
                                            data-name="${displayName.replace(/"/g, '&quot;')}"
                                            data-variation-label="${(variation.variation_name || '').replace(/"/g, '&quot;')}"
                                            data-price="${variation.default_sell_price}"
                                            data-is-variation="true">
                                            Add
                                        </button>
                                    </td>
                                </tr>
                            `;
                            variationsTableBody.insertAdjacentHTML('beforeend', row);
                        });
                    } else {
                        const row = `
                            <tr>
                                <td>${data.product.name}</td>
                                <td>${Number(data.price).toFixed(2)}</td>
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm quantity-input" id="qty_prod_${data.product.id}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success add-item-btn" 
                                        data-product-id="${data.product.id}" 
                                        data-variation-id=""
                                        data-name="${data.product.name}"
                                        data-variation-label=""
                                        data-price="${data.price}"
                                        data-is-variation="false">
                                        Add
                                    </button>
                                </td>
                            </tr>
                        `;
                        variationsTableBody.insertAdjacentHTML('beforeend', row);
                    }
                })
                .catch(error => {
                    console.error('Error fetching variations:', error);
                    variationsTableBody.innerHTML = `<tr><td colspan="4" class="text-danger">Could not load variations. ${error.message || ''}</td></tr>`;
                    variationsContainer.style.display = 'block';
                });
        });

        variationsTableBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-item-btn')) {
                const btn = e.target;
                const productId = btn.dataset.productId;
                const variationId = btn.dataset.variationId;
                const name = btn.dataset.name;
                const variationLabel = btn.dataset.variationLabel || '';
                const price = parseFloat(btn.dataset.price);
                const isVariation = btn.dataset.isVariation === 'true';
                
                let qtyInputId = isVariation ? `qty_var_${variationId}` : `qty_prod_${productId}`;
                const qtyInput = document.getElementById(qtyInputId);
                const quantity = parseInt(qtyInput.value);

                if (!quantity || quantity < 1) {
                    alert('Please enter a valid quantity');
                    return;
                }

                addItemToOrder(productId, variationId, name, variationLabel, price, quantity);
                qtyInput.value = '';
            }
        });

        function addItemToOrder(productId, variationId, name, variationLabel, price, quantity) {
            const total = price * quantity;
            orderTotal += total;
            itemCount++;

            const row = `
                <tr>
                    <td>${name}</td>
                    <td>${variationLabel || (variationId ? 'Variation' : '—')}</td>
                    <td>${price.toFixed(2)}</td>
                    <td>
                        <input type="hidden" name="items[${itemCount}][product_id]" value="${productId}">
                        <input type="hidden" name="items[${itemCount}][variation_id]" value="${variationId}">
                        <input type="hidden" name="items[${itemCount}][quantity]" value="${quantity}">
                        ${quantity}
                    </td>
                    <td>${total.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item-btn" data-total="${total}">Remove</button>
                    </td>
                </tr>
            `;
            orderItemsBody.insertAdjacentHTML('beforeend', row);
            updateGrandTotal();
            submitBtn.disabled = false;
        }

        orderItemsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item-btn')) {
                const btn = e.target;
                const total = parseFloat(btn.dataset.total);
                orderTotal -= total;
                btn.closest('tr').remove();
                updateGrandTotal();
                
                if (orderItemsBody.children.length === 0) {
                    submitBtn.disabled = true;
                }
            }
        });

        function updateGrandTotal() {
            grandTotalEl.textContent = orderTotal.toFixed(2);
        }
    });
</script>
@endpush
@endsection

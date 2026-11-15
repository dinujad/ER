@extends('erp.layout')

@section('title', 'Edit Order')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Order #{{ $order->order_number }}</h1>
    <p class="text-muted small mb-3">Pending / Processing orders only — add or remove items, then save.</p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
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
            @canRoute('orders.update')
<form method="POST" action="{{ route('orders.update', $order) }}">
                @csrf
                @method('PUT')

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <select class="form-control select2 @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) old('customer_id', $order->customer_id) === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->display_name }}@if($customer->phone) ({{ $customer->phone }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="sales_person_id" class="form-label">Sales Person</label>
                        <select class="form-control select2 @error('sales_person_id') is-invalid @enderror" id="sales_person_id" name="sales_person_id">
                            <option value="">-- Select Sales Person --</option>
                            @foreach($salesPersons as $sp)
                                <option value="{{ $sp->id }}" {{ (string) old('sales_person_id', $order->user_id) === (string) $sp->id ? 'selected' : '' }}>
                                    {{ $sp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('order_date') is-invalid @enderror" id="order_date" name="order_date"
                               value="{{ old('order_date', optional($order->order_date)->format('Y-m-d')) }}" required>
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
                        <tbody id="variations_table_body"></tbody>
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
                        <tbody id="order_items_body"></tbody>
                        <tfoot>
                            <tr id="discount_row" style="display:none;">
                                <th colspan="4" class="text-end">Discount:</th>
                                <th id="discount_amount">0.00</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Total Amount:</th>
                                <th id="grand_total">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary me-md-2" id="submit_order_btn" disabled>Save Changes</button>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
@endcanRoute
        </div>
    </div>
</div>

@php
    $existingItems = $order->items->map(function ($item) {
        $productName = $item->product?->name ?? 'Product';
        $variationName = $item->variation?->variation_name;
        return [
            'product_id' => $item->product_id,
            'variation_id' => $item->product_variation_id,
            'name' => $variationName ? ($productName . ' — ' . $variationName) : $productName,
            'variation_label' => $variationName ?? '',
            'price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
        ];
    })->values();
@endphp

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

        const existingItems = @json($existingItems);
        const discountType = @json($order->discount_type ?: 'fixed');
        const discountValue = @json((float) ($order->discount_value ?? 0));
        existingItems.forEach(item => {
            addItemToOrder(
                item.product_id,
                item.variation_id || '',
                item.name,
                item.variation_label || '',
                parseFloat(item.price),
                parseInt(item.quantity, 10)
            );
        });

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
                            variationsTableBody.insertAdjacentHTML('beforeend', `
                                <tr>
                                    <td>${displayName}</td>
                                    <td>${Number(variation.default_sell_price).toFixed(2)}</td>
                                    <td><input type="number" min="1" class="form-control form-control-sm quantity-input" id="qty_var_${variation.id}"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success add-item-btn"
                                            data-product-id="${variation.product_id}"
                                            data-variation-id="${variation.id}"
                                            data-name="${displayName.replace(/"/g, '&quot;')}"
                                            data-variation-label="${(variation.variation_name || '').replace(/"/g, '&quot;')}"
                                            data-price="${variation.default_sell_price}"
                                            data-is-variation="true">Add</button>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        variationsTableBody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${data.product.name}</td>
                                <td>${Number(data.price).toFixed(2)}</td>
                                <td><input type="number" min="1" class="form-control form-control-sm quantity-input" id="qty_prod_${data.product.id}"></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success add-item-btn"
                                        data-product-id="${data.product.id}"
                                        data-variation-id=""
                                        data-name="${data.product.name}"
                                        data-variation-label=""
                                        data-price="${data.price}"
                                        data-is-variation="false">Add</button>
                                </td>
                            </tr>
                        `);
                    }
                })
                .catch(error => {
                    console.error('Error fetching variations:', error);
                    variationsTableBody.innerHTML = `<tr><td colspan="4" class="text-danger">Could not load variations. ${error.message || ''}</td></tr>`;
                });
        });

        variationsTableBody.addEventListener('click', function(e) {
            if (!e.target.classList.contains('add-item-btn')) return;
            const btn = e.target;
            const productId = btn.dataset.productId;
            const variationId = btn.dataset.variationId;
            const isVariation = btn.dataset.isVariation === 'true';
            const qtyInput = document.getElementById(isVariation ? `qty_var_${variationId}` : `qty_prod_${productId}`);
            const quantity = parseInt(qtyInput.value, 10);
            if (!quantity || quantity < 1) {
                alert('Please enter a valid quantity');
                return;
            }
            addItemToOrder(productId, variationId, btn.dataset.name, btn.dataset.variationLabel || '', parseFloat(btn.dataset.price), quantity);
            qtyInput.value = '';
        });

        function addItemToOrder(productId, variationId, name, variationLabel, price, quantity) {
            const total = price * quantity;
            orderTotal += total;
            itemCount++;
            orderItemsBody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>${name}</td>
                    <td>${variationLabel || (variationId ? 'Variation' : '—')}</td>
                    <td>${price.toFixed(2)}</td>
                    <td>
                        <input type="hidden" name="items[${itemCount}][product_id]" value="${productId}">
                        <input type="hidden" name="items[${itemCount}][variation_id]" value="${variationId || ''}">
                        <input type="hidden" name="items[${itemCount}][quantity]" value="${quantity}">
                        ${quantity}
                    </td>
                    <td>${total.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-item-btn" data-total="${total}">Remove</button></td>
                </tr>
            `);
            updateGrandTotal();
            submitBtn.disabled = false;
        }

        orderItemsBody.addEventListener('click', function(e) {
            if (!e.target.classList.contains('remove-item-btn')) return;
            const btn = e.target;
            orderTotal -= parseFloat(btn.dataset.total);
            btn.closest('tr').remove();
            updateGrandTotal();
            submitBtn.disabled = orderItemsBody.children.length === 0;
        });

        function updateGrandTotal() {
            const value = Number(discountValue) || 0;
            let discount = 0;
            if (value > 0) {
                discount = discountType === 'percentage'
                    ? orderTotal * (Math.min(100, value) / 100)
                    : Math.min(value, orderTotal);
            }
            const discountRow = document.getElementById('discount_row');
            const discountEl = document.getElementById('discount_amount');
            if (discount > 0) {
                discountRow.style.display = '';
                discountEl.textContent = '-' + discount.toFixed(2);
            } else {
                discountRow.style.display = 'none';
                discountEl.textContent = '0.00';
            }
            grandTotalEl.textContent = Math.max(0, orderTotal - discount).toFixed(2);
        }
    });
</script>
@endpush
@endsection

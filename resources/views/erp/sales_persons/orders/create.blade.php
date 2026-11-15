@extends('sales_person.layout')

@section('content')
<div class="container-fluid">
    <style>
        #variations_table_body tr.is-added > td { background: #e8f6ee; }
        #variations_table_body tr.is-added > td:first-child { box-shadow: inset 4px 0 0 #157347; }
    </style>
    <h1 class="h3 mb-4 text-gray-800">{{ !empty($editingOrder) ? 'Edit Order' : 'Create New Order' }}</h1>

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
            <form method="POST" action="{{ !empty($editingOrder) ? route('sales-person.orders.update', $editingOrder) : route('sales-person.orders.store') }}" id="sales_order_form">
                @csrf
                @if(!empty($editingOrder))
                    @method('PUT')
                @endif
                <input type="hidden" name="draft_id" id="draft_id" value="{{ $draftPayload['id'] ?? '' }}">
                <div id="draft_status" class="small text-muted mb-2"></div>
                
                <div class="mb-4">
                    @include('sales_person.partials.customer-search', [
                        'selectedCustomer' => $selectedCustomer ?? null,
                    ])
                </div>

                <hr>

                <h5 class="mb-3">Add Products</h5>

                <div class="row mb-3">
                    <div class="col-lg-8">
                        @include('sales_person.partials.product-search', [
                            'mode' => 'order-form',
                            'initialQuery' => request('q', ''),
                        ])
                    </div>
                </div>
                <div id="stock_hint" class="alert alert-info py-2 px-3 mb-3" style="display: none;"></div>

                <div id="variations_container" class="mb-4" style="display: none;">
                    <div id="bulk_order_bar" class="border rounded px-3 py-2 mb-2 bg-light" style="display: none;">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="bulk_same_qty">
                            <label class="form-check-label fw-semibold" for="bulk_same_qty">හැම variation එකටම එකම ගණන (Bulk order)</label>
                        </div>
                        <p class="small text-muted mb-2 mb-md-1">Design number එකේ හැම variation එකෙන්ම එකම ගණන ගන්න ඕන නම් මෙය tick කරන්න. ගණන මෙතන දාලා එකවර ඇතුළත් කරන්න. Tick නැතුව තිබ්බොත් පරණ විදිහට variation එකකට වෙනම ගණන දාන්න පුළුවන්.</p>
                        <div id="bulk_qty_row" class="d-none flex-wrap gap-2 align-items-center">
                            <input type="number" min="1" id="bulk_qty" class="form-control form-control-sm" style="max-width: 8rem;" placeholder="ගණන">
                            <button type="button" class="btn btn-sm btn-success" id="bulk_add_btn">හැම variation එකටම දාන්න</button>
                        </div>
                    </div>
                    <h6>Available Variations</h6>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Design No (SKU)</th>
                                <th>Variation</th>
                                <th>Price</th>
                                <th>Available</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="variations_table_body">
                            <!-- Rows will be added here dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <h6>Order Items</h6>
                    <table class="table table-striped" id="order_items_table">
                        <thead>
                            <tr>
                                <th>Design No (SKU)</th>
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
                                <th colspan="4" class="text-end">Subtotal:</th>
                                <th id="subtotal_display">0.00</th>
                                <th></th>
                            </tr>
                            <tr id="discount_row" style="display: none;">
                                <th colspan="4" class="text-end text-danger">Discount:</th>
                                <th class="text-danger" id="discount_amount_display">0.00</th>
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

                <div class="border rounded px-2 py-2 mt-3 mb-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-lg-2">
                            <label for="discount_type" class="form-label small mb-1">Discount</label>
                            <select name="discount_type" id="discount_type" class="form-select form-select-sm">
                                <option value="fixed" {{ old('discount_type', 'fixed') === 'fixed' ? 'selected' : '' }}>Rs</option>
                                <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>%</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="discount_value" class="form-label small mb-1">Value</label>
                            <input type="number" name="discount_value" id="discount_value" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount_value', 0) }}">
                            <div class="d-none" id="discount_hint"></div>
                        </div>
                        <div class="col-12 col-lg-2">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="is_urgent" id="is_urgent" value="1" {{ old('is_urgent') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="is_urgent">Urgent</label>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label for="notes" class="form-label small mb-1">Note <span class="text-muted fw-normal">admin only, not on the order form</span></label>
                            <input type="text" name="notes" id="notes" class="form-control form-control-sm @error('notes') is-invalid @enderror" maxlength="2000" placeholder="Optional note" value="{{ old('notes') }}">
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    @if(empty($editingOrder))
                        <button type="button" class="btn btn-outline-primary btn-sm" id="save_draft_btn">Save Draft</button>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm" id="submit_order_btn" disabled>{{ !empty($editingOrder) ? 'Update Order' : 'Place Order' }}</button>
                    <a href="{{ !empty($editingOrder) ? route('sales-person.orders.show', $editingOrder) : route('sales-person.orders.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const variationsContainer = document.getElementById('variations_container');
        const variationsTableBody = document.getElementById('variations_table_body');
        const orderItemsBody = document.getElementById('order_items_body');
        const grandTotalEl = document.getElementById('grand_total');
        const subtotalEl = document.getElementById('subtotal_display');
        const submitBtn = document.getElementById('submit_order_btn');
        const stockHint = document.getElementById('stock_hint');
        const discountTypeEl = document.getElementById('discount_type');
        const discountValueEl = document.getElementById('discount_value');
        const discountRowEl = document.getElementById('discount_row');
        const discountAmountEl = document.getElementById('discount_amount_display');
        const discountHintEl = document.getElementById('discount_hint');
        let orderTotal = 0;
        let itemCount = 0;

        function calculateDiscountAmount(subtotal) {
            const value = parseFloat(discountValueEl.value) || 0;
            if (value <= 0) {
                return 0;
            }
            if (discountTypeEl.value === 'percentage') {
                return subtotal * (Math.min(100, value) / 100);
            }
            return Math.min(value, subtotal);
        }

        function updateDiscountHint() {
            if (discountTypeEl.value === 'percentage') {
                discountHintEl.textContent = 'Enter percentage (e.g. 10 for 10%).';
            } else {
                discountHintEl.textContent = 'Enter amount in rupees.';
            }
        }

        discountTypeEl.addEventListener('change', function () {
            updateDiscountHint();
            updateGrandTotal();
        });
        discountValueEl.addEventListener('input', updateGrandTotal);
        updateDiscountHint();

        function formatStock(value) {
            const num = Number(value || 0);
            return Number.isInteger(num) ? num.toString() : num.toFixed(2);
        }

        function loadProductVariations(productId) {
            window.__spLastProductId = productId || '';
            scheduleDraftSave();
            if (!productId) {
                variationsContainer.style.display = 'none';
                stockHint.style.display = 'none';
                const bulkBar = document.getElementById('bulk_order_bar');
                if (bulkBar) bulkBar.style.display = 'none';
                return;
            }

            fetch(`{{ url('sales-person/products') }}/${productId}/variations`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    variationsTableBody.innerHTML = '';
                    variationsContainer.style.display = 'block';
                    const bulkBar = document.getElementById('bulk_order_bar');
                    const bulkTick = document.getElementById('bulk_same_qty');
                    const bulkQtyRow = document.getElementById('bulk_qty_row');
                    if (bulkTick) bulkTick.checked = false;
                    if (bulkQtyRow) {
                        bulkQtyRow.classList.add('d-none');
                        bulkQtyRow.classList.remove('d-flex');
                    }

                    if (data.type === 'variable' && Array.isArray(data.variations) && data.variations.length > 0) {
                        if (bulkBar) bulkBar.style.display = 'block';
                        stockHint.style.display = 'block';
                        stockHint.innerHTML = '<strong>Available stock:</strong> variation row එකෙන් බලන්න. Qty වැඩි උනත් order ගන්න පුළුවන්.';

                        data.variations.forEach(variation => {
                            const row = `
                                <tr data-picker-product-id="${variation.product_id}" data-picker-variation-id="${variation.id}">
                                    <td>
                                        <strong>${variation.product_sku || data.product_sku || '—'}</strong>
                                        <div class="small text-muted">${variation.product_name || data.product_name || ''}</div>
                                    </td>
                                    <td>
                                        ${variation.variation_name}
                                        <span class="badge bg-success added-badge d-none ms-1">Added</span>
                                    </td>
                                    <td>${variation.default_sell_price}</td>
                                    <td><span class="badge bg-info text-dark">Available: ${formatStock(variation.available_stock)}</span></td>
                                    <td>
                                        <input type="number" min="1" class="form-control form-control-sm quantity-input" id="qty_var_${variation.id}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success add-item-btn" 
                                            data-product-id="${variation.product_id}" 
                                            data-variation-id="${variation.id}"
                                            data-design-no="${variation.product_sku || data.product_sku || ''}"
                                            data-product-name="${variation.product_name || data.product_name || ''}"
                                            data-name="${variation.variation_name}"
                                            data-price="${variation.default_sell_price}"
                                            data-available-stock="${variation.available_stock}"
                                            data-is-variation="true">
                                            Add
                                        </button>
                                    </td>
                                </tr>
                            `;
                            variationsTableBody.insertAdjacentHTML('beforeend', row);
                        });
                        refreshPickerHighlights();
                        applySavedPicker();
                    } else {
                        if (bulkBar) bulkBar.style.display = 'none';
                        stockHint.style.display = 'block';
                        stockHint.innerHTML = `<strong>Available:</strong> ${formatStock(data.available_stock)} (Qty වැඩි උනත් order ගන්න පුළුවන්)`;

                        const row = `
                            <tr data-picker-product-id="${data.product.id}" data-picker-variation-id="">
                                <td>
                                    <strong>${data.product.sku || '—'}</strong>
                                    <div class="small text-muted">${data.product.name}</div>
                                </td>
                                <td>— <span class="badge bg-success added-badge d-none ms-1">Added</span></td>
                                <td>${data.price}</td>
                                <td><span class="badge bg-info text-dark">Available: ${formatStock(data.available_stock)}</span></td>
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm quantity-input" id="qty_prod_${data.product.id}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success add-item-btn" 
                                        data-product-id="${data.product.id}" 
                                        data-variation-id=""
                                        data-design-no="${data.product.sku || ''}"
                                        data-product-name="${data.product.name}"
                                        data-name=""
                                        data-price="${data.price}"
                                        data-available-stock="${data.available_stock}"
                                        data-is-variation="false">
                                        Add
                                    </button>
                                </td>
                            </tr>
                        `;
                        variationsTableBody.insertAdjacentHTML('beforeend', row);
                        refreshPickerHighlights();
                        applySavedPicker();
                    }
                })
                .catch(error => console.error('Error fetching variations:', error));
        }

        window.spOnProductSelected = function(item) {
            loadProductVariations(item.id);
        };

        const urlParams = new URLSearchParams(window.location.search);
        const preProductId = urlParams.get('product_id');
        if (preProductId) {
            loadProductVariations(preProductId);
        }

        document.getElementById('bulk_same_qty')?.addEventListener('change', function () {
            const row = document.getElementById('bulk_qty_row');
            if (!row) return;
            row.classList.toggle('d-none', !this.checked);
            row.classList.toggle('d-flex', this.checked);
            if (!this.checked) return;
            document.getElementById('bulk_qty')?.focus();
        });

        document.getElementById('bulk_add_btn')?.addEventListener('click', function () {
            const tick = document.getElementById('bulk_same_qty');
            if (!tick || !tick.checked) return;
            const quantity = parseInt(document.getElementById('bulk_qty')?.value, 10);
            if (!quantity || quantity < 1) {
                alert('හැම variation එකටම දාන ගණන ඇතුළත් කරන්න.');
                return;
            }
            const buttons = variationsTableBody.querySelectorAll('.add-item-btn');
            if (!buttons.length) return;
            buttons.forEach(function (btn) {
                addItemToOrder(
                    btn.dataset.productId,
                    btn.dataset.variationId,
                    btn.dataset.designNo || '',
                    btn.dataset.productName || '',
                    btn.dataset.name,
                    parseFloat(btn.dataset.price),
                    quantity,
                    parseFloat(btn.dataset.availableStock || 0),
                    'set'
                );
            });
        });

        // Event delegation for "Add" buttons in variations table
        variationsTableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.add-item-btn');
            if (btn) {
                const productId = btn.dataset.productId;
                const variationId = btn.dataset.variationId;
                const designNo = btn.dataset.designNo || '';
                const productName = btn.dataset.productName || '';
                const name = btn.dataset.name;
                const price = parseFloat(btn.dataset.price);
                const availableStock = parseFloat(btn.dataset.availableStock || 0);
                const isVariation = btn.dataset.isVariation === 'true';
                
                let qtyInputId = isVariation ? `qty_var_${variationId}` : `qty_prod_${productId}`;
                const qtyInput = document.getElementById(qtyInputId);
                const quantity = parseInt(qtyInput.value);

                if (!quantity || quantity < 1) {
                    alert('Please enter a valid quantity');
                    return;
                }

                addItemToOrder(productId, variationId, designNo, productName, name, price, quantity, availableStock);
                qtyInput.value = ''; // Reset input
            }
        });

        function findExistingOrderRow(productId, variationId) {
            const vid = String(variationId || '');
            return Array.from(orderItemsBody.querySelectorAll('tr')).find(tr =>
                tr.dataset.productId === String(productId) &&
                (tr.dataset.variationId || '') === vid
            );
        }

        function addItemToOrder(productId, variationId, designNo, productName, variationName, price, quantity, availableStock, mode) {
            const existingRow = findExistingOrderRow(productId, variationId);

            if (existingRow) {
                const qtyHidden = existingRow.querySelector('.item-qty-hidden');
                const oldQty = parseInt(qtyHidden.value, 10) || 0;
                const newQty = mode === 'set' ? quantity : oldQty + quantity;
                const oldLineTotal = parseFloat(existingRow.querySelector('.remove-item-btn').dataset.total);
                const newTotal = price * newQty;

                orderTotal = orderTotal - oldLineTotal + newTotal;

                qtyHidden.value = newQty;
                existingRow.querySelector('.item-qty-display').textContent = newQty;
                existingRow.querySelector('.item-line-total').textContent = newTotal.toFixed(2);
                existingRow.querySelector('.remove-item-btn').dataset.total = newTotal;
                existingRow.dataset.availableStock = availableStock;

                const isOverStock = newQty > availableStock;
                const overBy = isOverStock ? newQty - availableStock : 0;
                const stockHint = existingRow.querySelector('.item-stock-hint');
                stockHint.className = 'small item-stock-hint ' + (isOverStock ? 'text-danger' : 'text-muted');
                stockHint.textContent = 'Available: ' + formatStock(availableStock) +
                    (isOverStock ? ' (Over by ' + formatStock(overBy) + ')' : '');

                updateGrandTotal();
                submitBtn.disabled = false;
                refreshPickerHighlights();
                scheduleDraftSave(true);
                return;
            }

            const total = price * quantity;
            orderTotal += total;
            itemCount++;
            const isOverStock = quantity > availableStock;
            const overBy = isOverStock ? quantity - availableStock : 0;

            const row = `
                <tr data-product-id="${productId}" data-variation-id="${variationId || ''}" data-price="${price}" data-available-stock="${availableStock}">
                    <td>
                        <input type="hidden" name="items[${itemCount}][product_id]" value="${productId}">
                        <input type="hidden" name="items[${itemCount}][variation_id]" value="${variationId}">
                        <strong>${designNo || '—'}</strong>
                        <div class="small text-muted">${productName}</div>
                    </td>
                    <td>${variationName || '—'}</td>
                    <td>${price.toFixed(2)}</td>
                    <td>
                        <input type="hidden" name="items[${itemCount}][quantity]" value="${quantity}" class="item-qty-hidden">
                        <span class="item-qty-display">${quantity}</span>
                        <div class="small item-stock-hint ${isOverStock ? 'text-danger' : 'text-muted'}">
                            Available: ${formatStock(availableStock)}${isOverStock ? ` (Over by ${formatStock(overBy)})` : ''}
                        </div>
                    </td>
                    <td class="item-line-total">${total.toFixed(2)}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary edit-item-btn">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger remove-item-btn" data-total="${total}">Remove</button>
                        </div>
                    </td>
                </tr>
            `;
            orderItemsBody.insertAdjacentHTML('beforeend', row);
            updateGrandTotal();
            submitBtn.disabled = false;
            refreshPickerHighlights();
            scheduleDraftSave(true);
        }

        function refreshPickerHighlights() {
            variationsTableBody.querySelectorAll('tr').forEach(function (pickerRow) {
                const orderRow = findExistingOrderRow(
                    pickerRow.dataset.pickerProductId,
                    pickerRow.dataset.pickerVariationId || ''
                );
                const badge = pickerRow.querySelector('.added-badge');
                if (orderRow) {
                    const qty = orderRow.querySelector('.item-qty-hidden').value;
                    pickerRow.classList.add('is-added');
                    if (badge) {
                        badge.textContent = 'Added · ' + qty;
                        badge.classList.remove('d-none');
                    }
                } else {
                    pickerRow.classList.remove('is-added');
                    if (badge) badge.classList.add('d-none');
                }
            });
        }

        function applyRowQuantity(row, newQty) {
            const price = parseFloat(row.dataset.price);
            const availableStock = parseFloat(row.dataset.availableStock || 0);
            const qtyHidden = row.querySelector('.item-qty-hidden');
            const oldQty = parseInt(qtyHidden.value, 10) || 0;
            const newTotal = price * newQty;
            orderTotal = orderTotal - (price * oldQty) + newTotal;
            qtyHidden.value = newQty;
            row.querySelector('.item-qty-display').textContent = newQty;
            row.querySelector('.item-line-total').textContent = newTotal.toFixed(2);
            row.querySelector('.remove-item-btn').dataset.total = newTotal;
            const isOverStock = newQty > availableStock;
            const overBy = isOverStock ? newQty - availableStock : 0;
            const stockHint = row.querySelector('.item-stock-hint');
            stockHint.className = 'small item-stock-hint ' + (isOverStock ? 'text-danger' : 'text-muted');
            stockHint.textContent = 'Available: ' + formatStock(availableStock) +
                (isOverStock ? ' (Over by ' + formatStock(overBy) + ')' : '');
            updateGrandTotal();
            refreshPickerHighlights();
            scheduleDraftSave(true);
        }

        function cancelRowEdit(row) {
            if (!row || !row.classList.contains('is-editing')) return;
            row.classList.remove('is-editing');
            row.querySelector('.item-qty-edit')?.remove();
            row.querySelector('.item-qty-display').style.display = '';
            const btn = row.querySelector('.edit-item-btn');
            btn.textContent = 'Edit';
            btn.classList.add('btn-outline-secondary');
            btn.classList.remove('btn-primary');
        }

        function startRowEdit(row) {
            orderItemsBody.querySelectorAll('tr.is-editing').forEach(function (other) {
                if (other !== row) cancelRowEdit(other);
            });
            row.classList.add('is-editing');
            const display = row.querySelector('.item-qty-display');
            const input = document.createElement('input');
            input.type = 'number';
            input.min = '1';
            input.className = 'form-control form-control-sm item-qty-edit';
            input.style.maxWidth = '90px';
            input.value = display.textContent.trim();
            display.style.display = 'none';
            display.after(input);
            const btn = row.querySelector('.edit-item-btn');
            btn.textContent = 'Save';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary');
            input.focus();
            input.select();
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    btn.click();
                }
            });
        }

        orderItemsBody.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-item-btn');
            if (editBtn) {
                const row = editBtn.closest('tr');
                if (row.classList.contains('is-editing')) {
                    const input = row.querySelector('.item-qty-edit');
                    const newQty = parseInt(input.value, 10);
                    if (!newQty || newQty < 1) {
                        alert('Please enter a valid quantity');
                        return;
                    }
                    applyRowQuantity(row, newQty);
                    cancelRowEdit(row);
                    return;
                }
                startRowEdit(row);
                return;
            }

            const btn = e.target.closest('.remove-item-btn');
            if (btn) {
                const row = btn.closest('tr');
                const total = parseFloat(btn.dataset.total);
                orderTotal -= total;
                row.remove();
                updateGrandTotal();
                refreshPickerHighlights();
                scheduleDraftSave(true);
                
                if (orderItemsBody.children.length === 0) {
                    submitBtn.disabled = true;
                }
            }
        });

        const draftStatus = document.getElementById('draft_status');
        const draftIdInput = document.getElementById('draft_id');
        const draftStorageKey = @json(!empty($editingOrder) ? 'sp-order-edit-'.(int) $editingOrder : 'sp-order-draft-'.(int) auth()->id());
        const serverDraft = @json($draftPayload ?? null);
        const draftUrl = @json(route('sales-person.orders.draft'));
        let restoringDraft = false;
        let draftSaveSeq = 0;

        function readLocalDraft() {
            try {
                return JSON.parse(localStorage.getItem(draftStorageKey) || 'null');
            } catch (e) {
                return null;
            }
        }

        function writeLocalDraft() {
            const customerHidden = document.querySelector('.sp-cs-hidden');
            const customerInput = document.querySelector('.sp-cs-input');
            const payload = {
                draftId: draftIdInput ? draftIdInput.value : '',
                customerId: customerHidden ? customerHidden.value : '',
                customerLabel: customerInput ? customerInput.value : '',
                notes: document.getElementById('notes') ? document.getElementById('notes').value : '',
                isUrgent: document.getElementById('is_urgent') ? document.getElementById('is_urgent').checked : false,
                discountType: discountTypeEl.value,
                discountValue: discountValueEl.value,
                lastProductId: window.__spLastProductId || '',
                picker: Array.from(document.querySelectorAll('#variations_table_body .quantity-input')).map(function (input) {
                    return { id: input.id, value: input.value };
                }),
                items: Array.from(orderItemsBody.querySelectorAll('tr')).map(function (row) {
                    return {
                        productId: row.dataset.productId,
                        variationId: row.dataset.variationId || '',
                        designNo: row.querySelector('strong') ? row.querySelector('strong').textContent : '',
                        productName: row.querySelector('.text-muted') ? row.querySelector('.text-muted').textContent : '',
                        variationName: row.children[1] ? row.children[1].textContent.trim() : '',
                        price: parseFloat(row.dataset.price) || 0,
                        quantity: parseInt(row.querySelector('.item-qty-hidden').value, 10) || 1,
                        availableStock: parseFloat(row.dataset.availableStock) || 0,
                    };
                }),
            };
            localStorage.setItem(draftStorageKey, JSON.stringify(payload));
            return payload;
        }

        function applySavedPicker() {
            const state = readLocalDraft();
            if (!state || !Array.isArray(state.picker)) return;
            state.picker.forEach(function (row) {
                const input = row.id ? document.getElementById(row.id) : null;
                if (input && row.value) input.value = row.value;
            });
        }

        function scheduleDraftSave() {
            if (restoringDraft) return;
            writeLocalDraft();
        }

        function saveDraftToServer() {
            const state = writeLocalDraft();
            const saveBtn = document.getElementById('save_draft_btn');
            if (!state.customerId) {
                if (draftStatus) draftStatus.textContent = 'Select a customer before saving the draft.';
                return;
            }
            const seq = ++draftSaveSeq;
            if (saveBtn) saveBtn.disabled = true;
            if (draftStatus) draftStatus.textContent = 'Saving draft…';
            const token = document.querySelector('#sales_order_form input[name=_token]');
            fetch(draftUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.value : '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    draft_id: state.draftId || null,
                    customer_id: state.customerId,
                    notes: state.notes,
                    is_urgent: state.isUrgent ? 1 : 0,
                    discount_type: state.discountType,
                    discount_value: state.discountValue,
                    items: state.items.map(function (item) {
                        return {
                            product_id: item.productId,
                            variation_id: item.variationId || null,
                            quantity: item.quantity,
                        };
                    }),
                }),
            })
                .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
                .then(function (result) {
                    if (seq !== draftSaveSeq) return;
                    if (!result.ok) {
                        if (saveBtn) saveBtn.disabled = false;
                        if (result.data && result.data.message === 'This draft was already placed.') {
                            localStorage.removeItem(draftStorageKey);
                        }
                        if (draftStatus) draftStatus.textContent = (result.data && result.data.message) || 'Draft could not be saved.';
                        return;
                    }
                    localStorage.removeItem(draftStorageKey);
                    window.location.href = @json(route('sales-person.orders.create'));
                })
                .catch(function () {
                    if (seq !== draftSaveSeq) return;
                    if (saveBtn) saveBtn.disabled = false;
                    if (draftStatus) draftStatus.textContent = 'Draft could not be saved. The order is still on this form.';
                });
        }

        function restoreCustomerLabel(label, customerId) {
            const container = document.querySelector('.sp-customer-search');
            if (!container || !customerId) return;
            const hidden = container.querySelector('.sp-cs-hidden');
            const input = container.querySelector('.sp-cs-input');
            const selected = container.querySelector('.sp-cs-selected');
            if (hidden) hidden.value = customerId;
            if (input && label) input.value = label;
            if (selected && label) {
                selected.textContent = '';
                const span = document.createElement('span');
                span.className = 'text-success';
                span.textContent = 'Selected: ' + label;
                selected.appendChild(span);
            }
        }

        function restoreDraftItems(items) {
            (items || []).forEach(function (item) {
                addItemToOrder(
                    item.productId || item.product_id,
                    item.variationId || item.variation_id || '',
                    item.designNo || item.design_no || '',
                    item.productName || item.product_name || '',
                    item.variationName || item.variation_name || '',
                    parseFloat(item.price) || 0,
                    parseInt(item.quantity, 10) || 1,
                    parseFloat(item.availableStock != null ? item.availableStock : item.available_stock) || 0
                );
            });
        }

        function serverDraftState() {
            if (!serverDraft || !serverDraft.id) return null;
            return {
                draftId: serverDraft.id,
                customerId: serverDraft.customer_id,
                customerLabel: serverDraft.customer_label,
                notes: serverDraft.notes || '',
                isUrgent: !!serverDraft.is_urgent,
                discountType: serverDraft.discount_type || 'fixed',
                discountValue: serverDraft.discount_value || 0,
                lastProductId: serverDraft.last_product_id || '',
                items: serverDraft.items || [],
            };
        }

        function bootDraft() {
            restoringDraft = true;
            const local = readLocalDraft();
            const openedId = serverDraft && serverDraft.id ? String(serverDraft.id) : '';
            const localId = local && local.draftId ? String(local.draftId) : '';
            let state = null;
            if (openedId && openedId !== localId) {
                state = serverDraftState();
                if (state) localStorage.setItem(draftStorageKey, JSON.stringify(state));
            } else if (local && (local.customerId || (local.items && local.items.length))) {
                state = local;
            } else {
                state = serverDraftState();
            }
            if (state && (state.customerId || (state.items && state.items.length))) {
                if (state.customerId) restoreCustomerLabel(state.customerLabel || '', state.customerId);
                if (draftIdInput && state.draftId) draftIdInput.value = state.draftId;
                const notes = document.getElementById('notes');
                const urgent = document.getElementById('is_urgent');
                if (notes) notes.value = state.notes || '';
                if (urgent) urgent.checked = !!state.isUrgent;
                if (state.discountType) discountTypeEl.value = state.discountType;
                if (state.discountValue != null) discountValueEl.value = state.discountValue;
                updateDiscountHint();
                restoreDraftItems(state.items || []);
                if (state.lastProductId) loadProductVariations(state.lastProductId);
            }
            restoringDraft = false;
        }

        document.getElementById('notes')?.addEventListener('input', function () { scheduleDraftSave(); });
        document.getElementById('is_urgent')?.addEventListener('change', function () { scheduleDraftSave(true); });
        discountTypeEl.addEventListener('change', function () { scheduleDraftSave(); });
        discountValueEl.addEventListener('input', function () { scheduleDraftSave(); });
        document.querySelector('.sp-cs-hidden')?.addEventListener('change', function () { scheduleDraftSave(); });
        document.getElementById('save_draft_btn')?.addEventListener('click', function () { saveDraftToServer(); });
        variationsTableBody.addEventListener('input', function (event) {
            if (event.target.classList.contains('quantity-input')) scheduleDraftSave();
        });

        function updateGrandTotal() {
            const subtotal = orderTotal;
            const discount = calculateDiscountAmount(subtotal);
            subtotalEl.textContent = subtotal.toFixed(2);
            if (discount > 0) {
                discountRowEl.style.display = '';
                discountAmountEl.textContent = '-' + discount.toFixed(2);
            } else {
                discountRowEl.style.display = 'none';
                discountAmountEl.textContent = '0.00';
            }
            grandTotalEl.textContent = Math.max(0, subtotal - discount).toFixed(2);
        }

        document.getElementById('sales_order_form')?.addEventListener('submit', function (event) {
            let blocked = false;
            orderItemsBody.querySelectorAll('tr.is-editing').forEach(function (row) {
                const input = row.querySelector('.item-qty-edit');
                const newQty = parseInt(input ? input.value : '', 10);
                if (!newQty || newQty < 1) {
                    blocked = true;
                    return;
                }
                applyRowQuantity(row, newQty);
                cancelRowEdit(row);
            });
            if (blocked) {
                event.preventDefault();
                alert('Please enter a valid quantity');
            }
        });

        bootDraft();
    });
</script>
@endsection

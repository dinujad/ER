@extends('erp.pos.layout')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-0 rounded-0" role="alert">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
<div id="pos_toast_container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<div class="pos-wrapper">
    <!-- Top Bar (UltimatePOS style) -->
    <header class="pos-topbar">
        <a href="{{ route('erp.dashboard') }}" class="btn btn-pos-back me-2" title="Back"><i class="bi bi-arrow-left"></i></a>
        <select class="form-select form-select-sm pos-location-select" id="pos_store_select" title="Location" style="width: 180px;">
            @foreach($stores as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
            @if($stores->isEmpty())
                <option value="">No store</option>
            @endif
        </select>
        <span class="pos-datetime"><i class="bi bi-calendar3 me-1"></i><span id="pos_date_time"></span></span>
        <div class="pos-top-icons">
            <button type="button" class="btn btn-pos-icon" title="Refresh" id="pos_btn_refresh"><i class="bi bi-arrow-clockwise"></i></button>
            <button type="button" class="btn btn-pos-icon" title="Print" id="pos_btn_print"><i class="bi bi-printer"></i></button>
            <button type="button" class="btn btn-pos-icon" title="Calculator"><i class="bi bi-calculator"></i></button>
            <button type="button" class="btn btn-pos-icon" title="Fullscreen" id="pos_btn_fullscreen"><i class="bi bi-fullscreen"></i></button>
        </div>
        <div class="pos-top-actions ms-auto d-flex align-items-center gap-2">
            <span class="small text-muted" id="pos_register_status_label">Register: —</span>
            <button type="button" class="btn btn-sm btn-success d-none" id="pos_btn_open_register"><i class="bi bi-unlock me-1"></i>Open Register</button>
            <button type="button" class="btn btn-sm btn-warning d-none" id="pos_btn_close_register"><i class="bi bi-lock me-1"></i>Close Register</button>
            <a href="{{ route('retail-quotations.index') }}" class="btn btn-pos-outline btn-sm"><i class="bi bi-file-earmark-text me-1"></i>Quotations</a>
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_repair"><i class="bi bi-tools me-1"></i>Repair</button>
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_expense"><i class="bi bi-cash-stack me-1"></i>Add Expense</button>
        </div>
    </header>

    <div class="pos-main">
        <!-- Left: Cart -->
        <aside class="pos-order-panel">
            <div class="pos-customer-row">
                <label class="pos-label"><i class="bi bi-person me-1"></i>Customer</label>
                <div class="d-flex gap-1">
                    <select class="form-select form-select-sm pos-customer-select" id="customer_select">
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $c->phone === 'WALKIN' ? 'selected' : '' }}>{{ $c->name }}{{ $c->phone && $c->phone !== 'WALKIN' ? ' (' . $c->phone . ')' : '' }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-pos-icon" id="pos_add_customer_btn" title="Add customer"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
            <div class="pos-search-row mt-2">
                <label class="pos-label">Search or scan</label>
                <div class="pos-search-wrap">
                    <input type="text" class="form-control form-control-sm pos-search" id="pos_search" placeholder="Enter product name / SKU / Scan barcode" autocomplete="off">
                    <button type="button" class="btn btn-pos-icon" id="pos_search_clear" title="Clear"><i class="bi bi-x-lg"></i></button>
                </div>
                <div id="pos_search_results" class="pos-search-results mt-1"></div>
            </div>
            <div class="pos-table-wrap">
                <table class="pos-order-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center" style="width: 90px;">Qty</th>
                            <th class="text-end" style="width: 90px;">Subtotal</th>
                            <th style="width: 36px;"></th>
                        </tr>
                    </thead>
                    <tbody id="order_items_body"></tbody>
                </table>
            </div>
            <div class="pos-summary">
                <div class="pos-summary-row">
                    <span>Items:</span>
                    <span id="summary_items">0</span>
                </div>
                <div class="pos-summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal_display">0.00</span>
                </div>
                <div class="pos-summary-row pos-discount-row">
                    <span>Discount (-)</span>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <select class="form-select form-select-sm pos-discount-type" id="discount_type" style="width: 88px;">
                            <option value="fixed">Fixed</option>
                            <option value="percentage">%</option>
                        </select>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm pos-discount-input" id="discount_input" style="width: 72px;" value="0">
                        <small class="text-muted" id="discount_preview">0.00</small>
                    </div>
                </div>
                <div class="pos-summary-row pos-tax-row">
                    <span>Tax (+)</span>
                    <div class="d-flex align-items-center gap-1">
                        <select class="form-select form-select-sm pos-tax-type" id="tax_type" style="width: 88px;">
                            <option value="percentage">%</option>
                            <option value="fixed">Fixed</option>
                        </select>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm pos-tax-input" id="tax_input" style="width: 72px;" value="0">
                    </div>
                </div>
                <div class="pos-summary-row">
                    <span>Shipping (+)</span>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm d-inline-block" id="shipping_input" style="width: 80px;" value="0">
                </div>
                <div class="pos-summary-row fw-bold border-top pt-2 mt-1">
                    <span>Total:</span>
                    <span id="grand_total_display">0.00</span>
                </div>
            </div>
            <div class="pos-clear-row">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clear_cart_btn"><i class="bi bi-trash me-1"></i>Clear cart</button>
            </div>
        </aside>

        <!-- Right: Product grid -->
        <section class="pos-products-panel">
            <div class="pos-filter-row">
                <button type="button" class="btn btn-pos-primary btn-sm active" data-filter="all"><i class="bi bi-grid-3x3-gap me-1"></i>Brands</button>
                @foreach($brands as $brand)
                    <button type="button" class="btn btn-pos-outline btn-sm" data-filter="brand-{{ $brand->id }}">{{ $brand->name }}</button>
                @endforeach
                <button type="button" class="btn btn-pos-outline btn-sm" data-filter-tab="categories">Categories</button>
            </div>
            <div class="pos-filter-row pos-categories-row d-none">
                <button type="button" class="btn btn-pos-outline btn-sm active" data-filter="all">All</button>
                @foreach($categories as $cat)
                    <button type="button" class="btn btn-pos-outline btn-sm" data-filter="category-{{ $cat->id }}">{{ $cat->name }}</button>
                @endforeach
            </div>
            <div class="pos-products-grid" id="products_grid">
                @foreach($products as $p)
                    @php $img = !empty($p['image']) ? asset('storage/' . $p['image']) : null; $stock = $p['retail_stock'] ?? 0; @endphp
                    <div class="pos-product-card" data-product-id="{{ $p['id'] }}" data-name="{{ strtolower($p['name']) }}" data-sku="{{ strtolower($p['sku'] ?? '') }}" data-brand-id="{{ $p['brand_id'] ?? '' }}" data-category-id="{{ $p['category_id'] ?? '' }}">
                        <div class="pos-product-img">
                            @if($img)<img src="{{ $img }}" alt="">@else<i class="bi bi-box-seam"></i>@endif
                        </div>
                        <div class="pos-product-info">
                            <div class="pos-product-name" title="{{ $p['name'] }}">{{ Str::limit($p['name'], 28) }}</div>
                            <div class="pos-product-meta">({{ $p['sku'] ?? $p['id'] }})</div>
                            <div class="pos-product-price">LKR {{ $p['price_label'] ?? number_format($p['retail_price'] ?? 0, 2) }}</div>
                            <div class="pos-product-stock">{{ number_format($stock, 0) }} pcs retail stock</div>
                        </div>
                    </div>
                @endforeach
                @if($products->isEmpty())
                    <div class="text-center text-muted py-5 w-100" style="grid-column: 1 / -1;">
                        <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                        No items with retail stock. Transfer stock to retail channel first.
                    </div>
                @endif
            </div>
        </section>
    </div>

    <!-- Bottom Sticky Bar -->
    <footer class="pos-bottom-bar">
        <div class="pos-bottom-actions">
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_draft">Draft</button>
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_quotation">Quotation</button>
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_suspend">Suspend</button>
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_credit">Credit Sale</button>
            <button type="button" class="btn btn-pos-outline btn-sm" id="pos_btn_card">Card</button>
            <button type="button" class="btn btn-pos-multiple btn-sm" id="pos_multiple_btn" disabled><i class="bi bi-wallet2 me-1"></i>Multiple Pay</button>
            <button type="button" class="btn btn-pos-cash btn-sm" id="pos_cash_btn" disabled><i class="bi bi-cash-coin me-1"></i>Cash</button>
            <button type="button" class="btn btn-pos-cancel btn-sm" id="btn_cancel_pos"><i class="bi bi-x-lg me-1"></i>Cancel</button>
        </div>
        <div class="pos-bottom-right">
            <button type="button" class="btn btn-pos-outline btn-sm me-3" id="pos_btn_recent"><i class="bi bi-clock-history me-1"></i>Recent Transactions</button>
            <div class="pos-total-payable">
                <span class="label">Total Payable:</span>
                <span class="value" id="grand_total">0.00</span>
            </div>
        </div>
    </footer>
</div>

<!-- Modal: Payment (unified – Cash / Card / Multiple Pay) -->
<div class="modal fade" id="posPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Payment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Advance Balance: Rs <span id="pos_advance_balance">0.00</span></p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="pos-payment-rows mb-3">
                            <label class="form-label small fw-bold">Amount <span class="text-danger">*</span></label>
                            <div id="pos_payment_rows">
                                <div class="pos-payment-row mb-2 border rounded p-2 bg-light">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-5">
                                            <label class="form-label small mb-0">Amount</label>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm pos-pay-amount" placeholder="0.00">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small mb-0">Payment Method</label>
                                            <select class="form-select form-select-sm pos-pay-method">
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                                <option value="mobile">Mobile</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100 pos-remove-pay-row" title="Remove">×</button>
                                        </div>
                                    </div>
                                    <div class="mt-1">
                                        <label class="form-label small mb-0">Payment note</label>
                                        <textarea class="form-control form-control-sm pos-pay-note" rows="1" placeholder="Optional"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="pos_add_payment_row"><i class="bi bi-plus me-1"></i>Add Payment Row</button>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-0">Sell note</label>
                            <textarea class="form-control form-control-sm" id="pos_sell_note" rows="2" placeholder="Optional"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small mb-0">Staff note</label>
                            <textarea class="form-control form-control-sm" id="pos_staff_note" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="pos-payment-summary rounded p-3">
                            <div class="d-flex justify-content-between small mb-2"><span>Total Items:</span><span id="pos_summary_items">0</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>Total Payable:</span><strong>Rs <span id="pos_summary_payable">0.00</span></strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>Total Paying:</span><strong>Rs <span id="pos_summary_paying">0.00</span></strong></div>
                            <div class="d-flex justify-content-between mb-2 text-success"><span>Change Return:</span><strong>Rs <span id="pos_summary_change">0.00</span></strong></div>
                            <div class="d-flex justify-content-between mb-0"><span>Balance:</span><strong>Rs <span id="pos_summary_balance">0.00</span></strong></div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="pos_finalize_payment_btn" disabled><i class="bi bi-check-lg me-1"></i>Finalize Payment</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Register Open -->
<div class="modal fade" id="posRegisterOpenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Open Register</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Opening cash amount</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pos_register_open_amount" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Note</label>
                    <input type="text" class="form-control form-control-sm" id="pos_register_open_note" placeholder="Optional">
                </div>
                <button type="button" class="btn btn-pos-primary w-100" id="pos_register_open_confirm">Open</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Register Close -->
<div class="modal fade" id="posRegisterCloseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Close Register</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="pos_register_close_summary" class="small mb-3"></div>
                <div class="mb-2">
                    <label class="form-label small">Counted cash</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="pos_register_close_counted">
                </div>
                <div class="mb-3"><strong>Difference:</strong> <span id="pos_register_close_diff">0.00</span></div>
                <button type="button" class="btn btn-pos-primary" id="pos_register_close_confirm">Close Register</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Expense -->
<div class="modal fade" id="posExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-cash-stack me-1"></i>Add Expense</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Category</label>
                    <select class="form-select form-select-sm" id="pos_expense_category">
                        @foreach(\App\Models\ExpenseCategory::where('is_active', true)->orderBy('name')->get() as $ec)
                            <option value="{{ $ec->id }}">{{ $ec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Amount</label>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" id="pos_expense_amount">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Payment</label>
                    <select class="form-select form-select-sm" id="pos_expense_method">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Note</label>
                    <input type="text" class="form-control form-control-sm" id="pos_expense_note" placeholder="Optional">
                </div>
                <button type="button" class="btn btn-pos-primary" id="pos_expense_confirm">Add Expense</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Repair -->
<div class="modal fade" id="posRepairModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-tools me-1"></i>Repair Ticket</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Customer</label>
                    <select class="form-select form-select-sm" id="pos_repair_customer">
                        @foreach($customers as $c)
                            @if($c->phone !== 'WALKIN')
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone ?? '-' }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Item / Device</label>
                    <input type="text" class="form-control form-control-sm" id="pos_repair_item" placeholder="Item name">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Issue</label>
                    <textarea class="form-control form-control-sm" id="pos_repair_issue" rows="2" placeholder="Description"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Estimate (LKR)</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="pos_repair_estimate" placeholder="0">
                </div>
                <button type="button" class="btn btn-pos-primary" id="pos_repair_confirm">Create Ticket</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Recent Transactions -->
<div class="modal fade" id="posRecentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-clock-history me-1"></i>Recent Transactions</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="pos_recent_list"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Variable product - choose variation -->
<div class="modal fade" id="posVariationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="posVariationModalTitle">Select variation</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" id="posVariationModalBody"></div>
        </div>
    </div>
</div>

<!-- Modal: Add retail customer -->
<div class="modal fade" id="posAddCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-person-plus me-1"></i>Add Retail Customer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pos_add_customer_form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Customer name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="Phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Address"></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-pos-primary" id="pos_add_customer_submit">Add Customer</button>
                    </div>
                </form>
                <div id="pos_add_customer_error" class="alert alert-danger mt-2 d-none"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* POS: Full-screen layout, no gap at bottom */
:root { --pos-primary: #1e3a5f; --pos-primary-dark: #0d2137; --pos-bg: #ffffff; --pos-border: #e2e8f0; --pos-head: #f8fafc; --pos-cash: #0d9488; --pos-cancel: #dc2626; }
.pos-wrapper { display: flex; flex-direction: column; height: 100%; min-height: 100vh; background: #f1f5f9; }
.pos-wrapper #pos_form { flex: 1; min-height: 0; display: flex; flex-direction: column; }
.btn-pos-back { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--pos-border); background: var(--pos-bg); border-radius: 8px; color: #64748b; text-decoration: none; }
.btn-pos-back:hover { background: var(--pos-head); color: var(--pos-primary); }
.pos-topbar { display: flex; align-items: center; gap: 1rem; padding: 12px 20px; background: var(--pos-bg); border-bottom: 1px solid var(--pos-border); flex-shrink: 0; }
.pos-location { font-weight: 600; font-size: 1.05rem; color: #334155; }
.pos-datetime { background: var(--pos-primary); color: #fff; padding: 8px 14px; border-radius: 8px; font-size: 0.9rem; }
.pos-top-actions { margin-left: auto; }
.btn-pos-outline { border: 1px solid var(--pos-primary); color: var(--pos-primary); background: transparent; padding: 8px 14px; border-radius: 8px; font-size: 0.875rem; text-decoration: none; }
.btn-pos-outline:hover { background: var(--pos-head); color: var(--pos-primary); }
.btn-pos-primary { background: var(--pos-primary); color: #fff; border: none; padding: 8px 14px; border-radius: 8px; }
.btn-pos-primary:hover, .btn-pos-primary.active { background: var(--pos-primary-dark); color: #fff; }
.btn-pos-icon { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--pos-border); background: var(--pos-bg); border-radius: 8px; color: #64748b; }
.btn-pos-icon:hover { background: var(--pos-head); color: var(--pos-primary); }
.pos-main { display: flex; flex: 1; min-height: 0; overflow: hidden; }
.pos-order-panel { width: 340px; min-width: 300px; max-width: 26vw; display: flex; flex-direction: column; background: var(--pos-bg); border-right: 1px solid var(--pos-border); padding: 12px; overflow: hidden; flex-shrink: 0; }
.pos-search-row { flex-shrink: 0; }
.pos-search-row .pos-search-wrap { display: flex; gap: 6px; }
.pos-search-row .pos-search-wrap .pos-search { flex: 1; }
.pos-search-results { max-height: 160px; overflow-y: auto; border: 1px solid var(--pos-border); border-radius: 8px; background: #fff; }
.pos-search-results .pos-search-item { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; cursor: pointer; font-size: 0.8rem; }
.pos-search-results .pos-search-item:hover { background: var(--pos-head); }
.pos-search-results .pos-search-item small { color: #64748b; }
.pos-tax-row .d-flex { align-items: center; }
.pos-top-icons { display: flex; gap: 6px; }
.pos-bottom-right { display: flex; align-items: center; }
.pos-payment-summary { background: #fff3e0; border: 1px solid #ffcc80; }
.pos-customer-row .pos-label { font-size: 0.75rem; color: #64748b; display: block; margin-bottom: 6px; font-weight: 500; }
.pos-customer-select { max-width: 100%; }
.pos-search-row { display: flex; gap: 8px; margin-top: 12px; }
.pos-search { border-radius: 8px; border: 1px solid var(--pos-border); flex: 1; }
.pos-table-wrap { flex: 1; min-height: 120px; overflow: auto; margin-top: 12px; border: 1px solid var(--pos-border); border-radius: 8px; }
.pos-order-table { width: 100%; font-size: 0.85rem; }
.pos-order-table thead { background: var(--pos-head); position: sticky; top: 0; z-index: 1; }
.pos-order-table th { padding: 10px 8px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid var(--pos-border); }
.pos-order-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.pos-order-table tbody tr:hover { background: #f8fafc; }
.pos-summary { margin-top: 12px; padding: 12px; background: var(--pos-head); border-radius: 8px; border: 1px solid var(--pos-border); flex-shrink: 0; }
.pos-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 0.9rem; }
.pos-discount-row { flex-wrap: wrap; gap: 6px; }
.pos-clear-row { margin-top: 10px; flex-shrink: 0; }
.pos-products-panel { flex: 1; display: flex; flex-direction: column; min-width: 0; padding: 20px; overflow: hidden; }
.pos-filter-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; flex-shrink: 0; }
.pos-products-grid { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; overflow-y: auto; overflow-x: hidden; padding-right: 10px; align-content: start; }
.pos-product-card { background: var(--pos-bg); border: 1px solid var(--pos-border); border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; text-align: center; min-height: 160px; }
.pos-product-card:hover { border-color: var(--pos-primary); box-shadow: 0 4px 12px rgba(30,58,95,0.12); }
.pos-product-img { width: 88px; height: 88px; border-radius: 10px; background: var(--pos-head); display: flex; align-items: center; justify-content: center; margin-bottom: 10px; color: #94a3b8; font-size: 2.25rem; overflow: hidden; flex-shrink: 0; }
.pos-product-img img { width: 100%; height: 100%; object-fit: cover; }
.pos-product-info { width: 100%; min-width: 0; }
.pos-product-name { font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pos-product-meta { font-size: 0.7rem; color: #94a3b8; margin-bottom: 2px; }
.pos-product-stock { font-size: 0.75rem; color: var(--pos-primary); font-weight: 500; }
.pos-bottom-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; background: var(--pos-bg); border-top: 1px solid var(--pos-border); flex-shrink: 0; }
.pos-bottom-actions { display: flex; gap: 10px; }
.pos-bottom-pay { display: flex; align-items: center; gap: 20px; }
.btn-pos-cash { background: var(--pos-cash); color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; }
.btn-pos-cash:hover:not(:disabled) { background: #0f766e; color: #fff; }
.btn-pos-cash:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-pos-multiple { background: #6366f1; color: #fff; border: none; padding: 12px 18px; border-radius: 8px; font-weight: 600; }
.btn-pos-multiple:hover:not(:disabled) { background: #4f46e5; color: #fff; }
.btn-pos-multiple:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-pos-cancel { background: var(--pos-cancel); color: #fff; border: none; padding: 12px 22px; border-radius: 8px; font-weight: 600; }
.btn-pos-cancel:hover { background: #b91c1c; color: #fff; }
.pos-total-payable { margin-left: 24px; }
.pos-total-payable .label { font-size: 0.95rem; color: #64748b; margin-right: 10px; }
.pos-total-payable .value { font-size: 1.6rem; font-weight: 700; color: var(--pos-primary); }
@media (min-width: 1400px) {
    .pos-products-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    .pos-product-img { width: 96px; height: 96px; font-size: 2.5rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    function getStoreId() { return document.getElementById('pos_store_select')?.value || ''; }
    function showToast(msg, type) {
        type = type || 'success';
        const c = document.getElementById('pos_toast_container');
        const el = document.createElement('div');
        el.className = 'alert alert-' + (type === 'error' ? 'danger' : type) + ' alert-dismissible fade show';
        el.innerHTML = msg + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        c.appendChild(el);
        setTimeout(function() { el.remove(); }, 4000);
    }
    function setDateTime() {
        const el = document.getElementById('pos_date_time');
        if (el) el.textContent = new Date().toLocaleString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setDateTime();
    setInterval(setDateTime, 1000);

    const orderItemsBody = document.getElementById('order_items_body');
    const grandTotalEl = document.getElementById('grand_total');
    const grandTotalDisplay = document.getElementById('grand_total_display');
    const subtotalEl = document.getElementById('subtotal_display');
    const summaryItemsEl = document.getElementById('summary_items');
    const cashBtn = document.getElementById('pos_cash_btn');
    const multipleBtn = document.getElementById('pos_multiple_btn');
    let posProducts = @json($products);
    let registerOpen = false;
    let currentRegisterId = null;
    let currentTotals = { total: 0, items_count: 0 };
    const posCartTotalsUrl = '{{ route("pos.cart.totals") }}';
    const posRegisterStatusUrl = '{{ route("pos.register.status") }}';
    const posBarcodeUrlBase = '{{ route("pos.products.barcode", ["code" => "__CODE__"]) }}';
    const posReceiptUrlBase = '{{ route("pos.receipt", ["order" => 0]) }}';
    const posSaleReturnUrlBase = '{{ route("retail-sale-returns.create", ["order_id" => "ORDER_ID"]) }}';

    fetch('{{ route("pos.products") }}').then(r => r.json()).then(data => { posProducts = data; }).catch(() => {});

    function refreshCart() {
        fetch(posCartTotalsUrl).then(r => r.json()).then(data => {
            const cart = data.cart || [];
            const meta = data.meta || {};
            const totals = data.totals || {};
            currentTotals = totals;
            orderItemsBody.innerHTML = '';
            cart.forEach((row, i) => {
                const name = row.product_name + (row.variation_name ? ' (' + row.variation_name + ')' : '');
                const subtotal = (row.quantity * row.unit_price).toFixed(2);
                const tr = document.createElement('tr');
                tr.innerHTML = '<td class="align-middle"><small>' + name + '</small><br><small class="text-muted">' + (row.sku || '') + '</small></td>' +
                    '<td class="text-center align-middle"><div class="d-flex align-items-center justify-content-center gap-1">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary p-1 pos-qty-minus" data-index="' + i + '">−</button>' +
                    '<input type="number" min="1" class="form-control form-control-sm text-center pos-qty-input" value="' + row.quantity + '" style="width:50px" data-index="' + i + '">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary p-1 pos-qty-plus" data-index="' + i + '">+</button></div></td>' +
                    '<td class="text-end align-middle">' + subtotal + '</td>' +
                    '<td class="align-middle"><button type="button" class="btn btn-sm btn-link text-danger p-0 pos-remove-item" data-index="' + i + '" title="Remove">×</button></td>';
                orderItemsBody.appendChild(tr);
            });
            orderItemsBody.querySelectorAll('.pos-qty-minus').forEach(btn => btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index, 10);
                const input = orderItemsBody.querySelector('.pos-qty-input[data-index="' + idx + '"]');
                const qty = Math.max(0, parseInt(input.value, 10) - 1);
                updateQty(idx, qty);
            }));
            orderItemsBody.querySelectorAll('.pos-qty-plus').forEach(btn => btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index, 10);
                const input = orderItemsBody.querySelector('.pos-qty-input[data-index="' + idx + '"]');
                updateQty(idx, parseInt(input.value, 10) + 1);
            }));
            orderItemsBody.querySelectorAll('.pos-qty-input').forEach(inp => inp.addEventListener('change', function() {
                const idx = parseInt(this.dataset.index, 10);
                const qty = Math.max(0, parseInt(this.value, 10) || 1);
                updateQty(idx, qty);
            }));
            orderItemsBody.querySelectorAll('.pos-remove-item').forEach(btn => btn.addEventListener('click', function() {
                removeItem(parseInt(this.dataset.index, 10));
            }));
            summaryItemsEl.textContent = totals.items_sum ?? totals.items_count ?? 0;
            subtotalEl.textContent = (totals.subtotal || 0).toFixed(2);
            grandTotalDisplay.textContent = (totals.total || 0).toFixed(2);
            grandTotalEl.textContent = (totals.total || 0).toFixed(2);
            document.getElementById('discount_type').value = meta.discount_type || 'fixed';
            document.getElementById('discount_input').value = meta.discount_value ?? 0;
            document.getElementById('tax_type').value = meta.tax_type || 'percentage';
            document.getElementById('tax_input').value = meta.tax_value ?? 0;
            document.getElementById('shipping_input').value = meta.shipping ?? 0;
            setPaymentButtonsState();
        }).catch(() => {});
    }

    function updateMeta() {
        const storeId = getStoreId();
        fetch('{{ route("pos.cart.meta") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                customer_id: document.getElementById('customer_select').value || null,
                discount_type: document.getElementById('discount_type').value,
                discount_value: parseFloat(document.getElementById('discount_input').value) || 0,
                tax_type: document.getElementById('tax_type').value,
                tax_value: parseFloat(document.getElementById('tax_input').value) || 0,
                shipping: parseFloat(document.getElementById('shipping_input').value) || 0
            })
        }).then(r => r.json()).then(data => { if (data.totals) { currentTotals = data.totals; grandTotalEl.textContent = (data.totals.total || 0).toFixed(2); grandTotalDisplay.textContent = (data.totals.total || 0).toFixed(2); setPaymentButtonsState(); } });
    }

    var lastAddKey = '';
    var lastAddTime = 0;
    function addToCartApi(productId, variationId, qty, cb) {
        var key = String(productId) + '-' + String(variationId || '');
        var now = Date.now();
        if (lastAddKey === key && (now - lastAddTime) < 600) {
            return;
        }
        lastAddKey = key;
        lastAddTime = now;
        fetch('{{ route("pos.cart.add") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ product_id: productId, variation_id: variationId || null, quantity: qty || 1 })
        }).then(r => r.json()).then(data => {
            if (data.success) { refreshCart(); if (cb) cb(); } else { showToast(data.message || 'Error', 'error'); }
        }).catch(() => showToast('Request failed', 'error'));
    }

    function updateQty(index, quantity) {
        fetch('{{ route("pos.cart.update") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ index, quantity })
        }).then(r => r.json()).then(data => { if (data.success) refreshCart(); else showToast(data.message || 'Error', 'error'); });
    }

    function removeItem(index) {
        fetch('{{ route("pos.cart.remove") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ index })
        }).then(r => r.json()).then(data => { if (data.success) refreshCart(); });
    }

    function setPaymentButtonsState() {
        const total = currentTotals.total || 0;
        const hasItems = (currentTotals.items_count || 0) > 0;
        const disabled = !hasItems || total <= 0;
        multipleBtn.disabled = disabled;
        cashBtn.disabled = disabled || !registerOpen;
        const cardBtn = document.getElementById('pos_btn_card');
        if (cardBtn) cardBtn.disabled = disabled;
    }

    function refreshRegisterStatus() {
        fetch(posRegisterStatusUrl + '?store_id=' + getStoreId()).then(r => r.json()).then(data => {
            registerOpen = data.open === true;
            currentRegisterId = data.register ? data.register.id : null;
            const label = document.getElementById('pos_register_status_label');
            const openBtn = document.getElementById('pos_btn_open_register');
            const closeBtn = document.getElementById('pos_btn_close_register');
            if (label) label.textContent = registerOpen ? 'Register: Open' : 'Register: Closed';
            if (openBtn) { openBtn.classList.toggle('d-none', registerOpen); }
            if (closeBtn) { closeBtn.classList.toggle('d-none', !registerOpen); }
            setPaymentButtonsState();
        });
    }

    refreshCart();
    refreshRegisterStatus();
    document.getElementById('pos_store_select').addEventListener('change', refreshRegisterStatus);

    document.getElementById('customer_select').addEventListener('change', function() { updateMeta(); });
    document.getElementById('discount_type').addEventListener('change', updateMeta);
    document.getElementById('discount_input').addEventListener('change', updateMeta);
    document.getElementById('tax_type').addEventListener('change', updateMeta);
    document.getElementById('tax_input').addEventListener('change', updateMeta);
    document.getElementById('shipping_input').addEventListener('change', updateMeta);

    document.getElementById('clear_cart_btn').addEventListener('click', function() {
        fetch('{{ route("pos.cart.clear") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
            .then(r => r.json()).then(data => { if (data.success) refreshCart(); });
    });

    document.querySelectorAll('.pos-product-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const product = posProducts.find(p => String(p.id) === String(productId));
            if (!product) return;
            if (product.product_type === 'variable' && product.variations && product.variations.length > 1) {
                const modal = new bootstrap.Modal(document.getElementById('posVariationModal'));
                document.getElementById('posVariationModalTitle').textContent = product.name;
                const body = document.getElementById('posVariationModalBody');
                body.innerHTML = product.variations.map(v => '<div class="d-flex justify-content-between align-items-center border-bottom py-2"><span>' + v.variation_name + '</span><span class="me-2 text-muted small">LKR ' + parseFloat(v.retail_price || v.default_sell_price || 0).toFixed(2) + ' · ' + parseFloat(v.retail_stock || 0).toFixed(0) + ' pcs</span><button type="button" class="btn btn-sm btn-pos-primary add-variation-btn" data-pid="' + product.id + '" data-vid="' + v.id + '">Add</button></div>').join('');
                body.querySelectorAll('.add-variation-btn').forEach(btn => btn.addEventListener('click', function() {
                    addToCartApi(this.dataset.pid, this.dataset.vid, 1, function() { modal.hide(); });
                }));
                modal.show();
            } else if (product.product_type === 'variable' && product.variations && product.variations.length === 1) {
                addToCartApi(productId, product.variations[0].id, 1);
            } else {
                addToCartApi(productId, null, 1);
            }
        });
    });

    var searchTimeout;
    document.getElementById('pos_search').addEventListener('input', function() {
        const term = this.value.trim();
        clearTimeout(searchTimeout);
        const resultsEl = document.getElementById('pos_search_results');
        const searchInput = document.getElementById('pos_search');
        if (term.length < 1) { resultsEl.innerHTML = ''; return; }
        searchTimeout = setTimeout(function() {
            fetch('{{ route("pos.products.search") }}?term=' + encodeURIComponent(term)).then(r => r.json()).then(items => {
                var exactMatch = items.find(function(it) {
                    var skuOk = (String(it.sku || '').trim().toLowerCase() === term.toLowerCase());
                    var barcodeOk = it.barcode && (String(it.barcode).trim().toLowerCase() === term.toLowerCase());
                    return skuOk || barcodeOk;
                });
                var shouldAutoAdd = exactMatch || items.length === 1;
                if (shouldAutoAdd) {
                    var toAdd = exactMatch || items[0];
                    addToCartApi(toAdd.id, toAdd.variation_id || null, 1);
                    searchInput.value = '';
                    resultsEl.innerHTML = '';
                    return;
                }
                resultsEl.innerHTML = items.map(it => '<div class="pos-search-item" data-pid="' + it.id + '" data-vid="' + (it.variation_id || '') + '"><strong>' + it.name + (it.variation_name ? ' ' + it.variation_name : '') + '</strong><br><small>' + it.sku + ' · LKR ' + (it.price || 0).toFixed(2) + ' · ' + it.stock + ' pcs</small></div>').join('');
                resultsEl.querySelectorAll('.pos-search-item').forEach(el => el.addEventListener('click', function() {
                    addToCartApi(this.dataset.pid, this.dataset.vid || null, 1);
                    searchInput.value = '';
                    resultsEl.innerHTML = '';
                }));
            });
        }, 200);
    });
    document.getElementById('pos_search').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const term = this.value.trim();
            if (term.length < 1) return;
            fetch(posBarcodeUrlBase.replace('__CODE__', encodeURIComponent(term))).then(r => r.json()).then(data => {
                if (data.product_id) {
                    addToCartApi(data.product_id, data.variation_id || null, 1);
                    document.getElementById('pos_search').value = '';
                    document.getElementById('pos_search_results').innerHTML = '';
                } else {
                    return fetch('{{ route("pos.products.search") }}?term=' + encodeURIComponent(term)).then(r2 => r2.json());
                }
            }).then(function(items) {
                if (items && Array.isArray(items) && items.length > 0) {
                    var exact = items.find(function(it) {
                        var skuOk = (String(it.sku || '').trim().toLowerCase() === term.toLowerCase());
                        var barcodeOk = it.barcode && (String(it.barcode).trim().toLowerCase() === term.toLowerCase());
                        return skuOk || barcodeOk;
                    });
                    if (exact) {
                        addToCartApi(exact.id, exact.variation_id || null, 1);
                    }
                    document.getElementById('pos_search').value = '';
                    document.getElementById('pos_search_results').innerHTML = '';
                }
            }).catch(function() {
                fetch('{{ route("pos.products.search") }}?term=' + encodeURIComponent(term)).then(r => r.json()).then(items => {
                    if (items.length > 0) {
                        var exact = items.find(function(it) {
                            var skuOk = (String(it.sku || '').trim().toLowerCase() === term.toLowerCase());
                            var barcodeOk = it.barcode && (String(it.barcode).trim().toLowerCase() === term.toLowerCase());
                            return skuOk || barcodeOk;
                        });
                        if (exact) addToCartApi(exact.id, exact.variation_id || null, 1);
                    }
                    document.getElementById('pos_search').value = '';
                    document.getElementById('pos_search_results').innerHTML = '';
                }).catch(() => showToast('Not found', 'error'));
            });
        }
    });
    document.getElementById('pos_search_clear').addEventListener('click', function() { document.getElementById('pos_search').value = ''; document.getElementById('pos_search_results').innerHTML = ''; });

    function getPayable() { return currentTotals.total || 0; }
    function doFinalize(payload, done) {
        fetch('{{ route("pos.sale.finalize") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(data => {
            if (data.success && data.redirect) { window.location.href = data.redirect; } else { showToast(data.message || 'Error', 'error'); if (done) done(); }
        }).catch(() => { showToast('Request failed', 'error'); if (done) done(); });
    }

    function getPaymentRowHtml() {
        return '<div class="pos-payment-row mb-2 border rounded p-2 bg-light">' +
            '<div class="row g-2 align-items-end"><div class="col-5"><label class="form-label small mb-0">Amount</label><input type="number" step="0.01" min="0" class="form-control form-control-sm pos-pay-amount" placeholder="0.00"></div>' +
            '<div class="col-5"><label class="form-label small mb-0">Payment Method</label><select class="form-select form-select-sm pos-pay-method"><option value="cash">Cash</option><option value="card">Card</option><option value="mobile">Mobile</option><option value="other">Other</option></select></div>' +
            '<div class="col-2"><button type="button" class="btn btn-outline-danger btn-sm w-100 pos-remove-pay-row" title="Remove">×</button></div></div>' +
            '<div class="mt-1"><label class="form-label small mb-0">Payment note</label><textarea class="form-control form-control-sm pos-pay-note" rows="1" placeholder="Optional"></textarea></div></div>';
    }
    function openPaymentModal(initialMethod, initialAmount) {
        if (getPayable() <= 0) return;
        if (initialMethod === 'cash' && !registerOpen) { showToast('Open the cash register first.', 'error'); return; }
        const payable = getPayable();
        const itemsCount = currentTotals.items_sum ?? currentTotals.items_count ?? 0;
        document.getElementById('pos_summary_items').textContent = itemsCount;
        document.getElementById('pos_summary_payable').textContent = payable.toFixed(2);
        document.getElementById('pos_sell_note').value = '';
        document.getElementById('pos_staff_note').value = '';
        const container = document.getElementById('pos_payment_rows');
        container.innerHTML = getPaymentRowHtml();
        const firstRow = container.querySelector('.pos-payment-row');
        const amountInp = firstRow.querySelector('.pos-pay-amount');
        const methodSel = firstRow.querySelector('.pos-pay-method');
        if (initialMethod) { methodSel.value = initialMethod; }
        amountInp.value = (initialAmount !== undefined ? initialAmount : payable).toFixed(2);
        function updatePaymentSummary() {
            let totalPaying = 0;
            container.querySelectorAll('.pos-pay-amount').forEach(inp => { totalPaying += parseFloat(inp.value) || 0; });
            const changeReturn = Math.max(0, totalPaying - payable);
            const balance = Math.max(0, payable - totalPaying);
            document.getElementById('pos_summary_paying').textContent = totalPaying.toFixed(2);
            document.getElementById('pos_summary_change').textContent = changeReturn.toFixed(2);
            document.getElementById('pos_summary_balance').textContent = balance.toFixed(2);
            document.getElementById('pos_finalize_payment_btn').disabled = totalPaying < (payable - 0.01);
        }
        container.querySelectorAll('.pos-pay-amount').forEach(inp => inp.addEventListener('input', updatePaymentSummary));
        container.querySelector('.pos-remove-pay-row').addEventListener('click', function() {
            if (container.querySelectorAll('.pos-payment-row').length <= 1) return;
            this.closest('.pos-payment-row').remove();
            updatePaymentSummary();
        });
        document.getElementById('pos_add_payment_row').onclick = function() {
            container.insertAdjacentHTML('beforeend', getPaymentRowHtml());
            const rows = container.querySelectorAll('.pos-payment-row');
            const last = rows[rows.length - 1];
            last.querySelector('.pos-pay-amount').addEventListener('input', updatePaymentSummary);
            last.querySelector('.pos-remove-pay-row').addEventListener('click', function() {
                if (container.querySelectorAll('.pos-payment-row').length <= 1) return;
                this.closest('.pos-payment-row').remove();
                updatePaymentSummary();
            });
            updatePaymentSummary();
        };
        updatePaymentSummary();
        document.getElementById('pos_finalize_payment_btn').onclick = function() {
            const payments = [];
            let amountTendered = null;
            container.querySelectorAll('.pos-payment-row').forEach(row => {
                const method = row.querySelector('.pos-pay-method').value;
                const amount = parseFloat(row.querySelector('.pos-pay-amount').value) || 0;
                if (amount > 0) {
                    payments.push({ method, amount });
                    if (method === 'cash' && (amountTendered === null || amount > amountTendered)) amountTendered = amount;
                }
            });
            let totalPaying = payments.reduce((s, p) => s + p.amount, 0);
            if (totalPaying < (payable - 0.01)) { showToast('Total paying must be at least Total Payable.', 'error'); return; }
            const payload = { payment_type: 'multiple', store_id: getStoreId() || null, payments };
            if (totalPaying > payable && amountTendered !== null) payload.amount_tendered = amountTendered;
            else if (totalPaying > payable) payload.amount_tendered = totalPaying;
            doFinalize(payload);
            bootstrap.Modal.getInstance(document.getElementById('posPaymentModal')).hide();
        };
        bootstrap.Modal.getOrCreateInstance(document.getElementById('posPaymentModal')).show();
    }
    document.getElementById('pos_cash_btn').addEventListener('click', function() { openPaymentModal('cash', getPayable()); });
    document.getElementById('pos_btn_card').addEventListener('click', function() { openPaymentModal('card', getPayable()); });
    multipleBtn.addEventListener('click', function() { openPaymentModal(null, getPayable()); });

    document.getElementById('pos_btn_credit').addEventListener('click', function() {
        if (getPayable() <= 0) return;
        const custSelect = document.getElementById('customer_select');
        const walkInOpt = Array.from(custSelect.options).find(o => o.textContent.indexOf('Walk-in') >= 0);
        if (walkInOpt && custSelect.value === walkInOpt.value) { showToast('Select a customer for credit sale.', 'error'); return; }
        doFinalize({ payment_type: 'credit', store_id: getStoreId() || null });
    });

    ['draft', 'quotation', 'suspend'].forEach(action => {
        document.getElementById('pos_btn_' + action).addEventListener('click', function() {
            if ((currentTotals.items_count || 0) === 0) { showToast('Cart is empty.', 'error'); return; }
            const url = action === 'draft' ? '{{ route("pos.sale.draft") }}' : action === 'quotation' ? '{{ route("pos.sale.quotation") }}' : '{{ route("pos.sale.suspend") }}';
            fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ store_id: getStoreId() || null }) })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        showToast(data.message);
                        refreshCart();
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        showToast(data.message || 'Error', 'error');
                    }
                });
        });
    });

    document.getElementById('btn_cancel_pos').addEventListener('click', function() {
        if ((currentTotals.items_count || 0) > 0 && confirm('Clear cart?')) document.getElementById('clear_cart_btn').click();
    });

    document.getElementById('pos_btn_expense').addEventListener('click', function() {
        document.getElementById('pos_expense_amount').value = '';
        document.getElementById('pos_expense_note').value = '';
        document.getElementById('pos_expense_confirm').onclick = function() {
            const amount = parseFloat(document.getElementById('pos_expense_amount').value);
            if (!amount || amount <= 0) { showToast('Enter amount.', 'error'); return; }
            fetch('{{ route("pos.expenses.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    expense_category_id: document.getElementById('pos_expense_category').value,
                    amount,
                    payment_method: document.getElementById('pos_expense_method').value,
                    note: document.getElementById('pos_expense_note').value,
                    store_id: getStoreId() || null
                })
            }).then(r => r.json()).then(data => { if (data.success) { showToast(data.message); bootstrap.Modal.getInstance(document.getElementById('posExpenseModal')).hide(); } else showToast(data.message || 'Error', 'error'); });
        };
        bootstrap.Modal.getOrCreateInstance(document.getElementById('posExpenseModal')).show();
    });

    document.getElementById('pos_btn_repair').addEventListener('click', function() {
        document.getElementById('pos_repair_item').value = '';
        document.getElementById('pos_repair_issue').value = '';
        document.getElementById('pos_repair_estimate').value = '';
        document.getElementById('pos_repair_confirm').onclick = function() {
            const item = document.getElementById('pos_repair_item').value.trim();
            if (!item) { showToast('Enter item/device.', 'error'); return; }
            fetch('{{ route("pos.repairs.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    customer_id: document.getElementById('pos_repair_customer').value,
                    item,
                    issue: document.getElementById('pos_repair_issue').value,
                    estimate: document.getElementById('pos_repair_estimate').value || null
                })
            }).then(r => r.json()).then(data => { if (data.success) { showToast(data.message); bootstrap.Modal.getInstance(document.getElementById('posRepairModal')).hide(); } else showToast(data.message || 'Error', 'error'); });
        };
        bootstrap.Modal.getOrCreateInstance(document.getElementById('posRepairModal')).show();
    });

    document.getElementById('pos_btn_recent').addEventListener('click', function() {
        fetch('{{ route("pos.sales.recent") }}?store_id=' + getStoreId()).then(r => r.json()).then(data => {
            const list = document.getElementById('pos_recent_list');
            const sales = data.sales || [];
            list.innerHTML = sales.length ? '<table class="table table-sm"><thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Payment</th><th>Date</th><th></th></tr></thead><tbody>' + sales.map(s => '<tr><td>' + (s.invoice_no || '') + '</td><td>' + (s.customer || '') + '</td><td>' + (s.total || 0).toFixed(2) + '</td><td>' + (s.payment_type || '') + '</td><td>' + (s.date || '') + '</td><td class="text-nowrap"><a href="' + posReceiptUrlBase.replace(/\/0$/, '/' + s.id) + '" target="_blank" class="btn btn-sm btn-outline-primary me-1">Print</a><a href="' + posSaleReturnUrlBase.replace(/ORDER_ID/, s.id) + '" class="btn btn-sm btn-outline-warning">Return</a></td></tr>').join('') + '</tbody></table>' : '<p class="text-muted mb-0">No recent sales.</p>';
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('posRecentModal')).show();
    });

    document.getElementById('pos_btn_open_register').addEventListener('click', function() {
        document.getElementById('pos_register_open_amount').value = '0';
        document.getElementById('pos_register_open_note').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('posRegisterOpenModal')).show();
    });
    document.getElementById('pos_register_open_confirm').addEventListener('click', function() {
        const storeId = getStoreId();
        if (!storeId) { showToast('Select a store.', 'error'); return; }
        const amount = parseFloat(document.getElementById('pos_register_open_amount').value) || 0;
        fetch('{{ route("pos.register.open") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ store_id: storeId, opening_cash: amount, note: document.getElementById('pos_register_open_note').value })
        }).then(r => r.json()).then(data => {
            if (data.success) { showToast('Register opened.'); refreshRegisterStatus(); bootstrap.Modal.getInstance(document.getElementById('posRegisterOpenModal')).hide(); }
            else showToast(data.message || 'Error', 'error');
        });
    });
    document.getElementById('pos_btn_close_register').addEventListener('click', function() {
        if (!currentRegisterId) return;
        document.getElementById('pos_register_close_counted').value = '';
        document.getElementById('pos_register_close_summary').innerHTML = '<p class="text-muted small">Enter counted cash and click Close.</p>';
        document.getElementById('pos_register_close_confirm').onclick = function() {
            const counted = parseFloat(document.getElementById('pos_register_close_counted').value);
            if (isNaN(counted) || counted < 0) { showToast('Enter counted cash.', 'error'); return; }
            fetch('{{ route("pos.register.close") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ register_id: currentRegisterId, counted_cash: counted })
            }).then(r => r.json()).then(data => {
                if (data.success) { showToast('Register closed. Difference: ' + (data.summary && data.summary.difference !== undefined ? data.summary.difference : 0)); refreshRegisterStatus(); bootstrap.Modal.getInstance(document.getElementById('posRegisterCloseModal')).hide(); }
                else showToast(data.message || 'Error', 'error');
            });
        };
        bootstrap.Modal.getOrCreateInstance(document.getElementById('posRegisterCloseModal')).show();
    });
    document.getElementById('pos_btn_refresh').addEventListener('click', function() { refreshCart(); refreshRegisterStatus(); });
    document.querySelectorAll('.pos-filter-row button[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.pos-filter-row button[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            const grid = document.getElementById('products_grid');
            grid.querySelectorAll('.pos-product-card').forEach(card => {
                if (filter === 'all') card.style.display = '';
                else card.style.display = (card.dataset.brandId === filter.replace('brand-', '') || card.dataset.categoryId === filter.replace('category-', '')) ? '' : 'none';
            });
        });
    });
    if (document.querySelector('.pos-filter-row button[data-filter-tab="categories"]')) {
        document.querySelector('.pos-filter-row button[data-filter-tab="categories"]').addEventListener('click', function() {
            document.querySelector('.pos-filter-row:not(.pos-categories-row) button[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelector('.pos-categories-row').classList.remove('d-none');
            document.querySelector('.pos-filter-row .pos-products-panel') && document.querySelectorAll('.pos-filter-row button[data-filter="all"]').forEach(b => b.click());
        });
    }

    const addCustomerModal = document.getElementById('posAddCustomerModal');
    const addCustomerForm = document.getElementById('pos_add_customer_form');
    const addCustomerError = document.getElementById('pos_add_customer_error');
    document.getElementById('pos_add_customer_btn').addEventListener('click', function() { addCustomerError.classList.add('d-none'); addCustomerForm.reset(); bootstrap.Modal.getOrCreateInstance(addCustomerModal).show(); });
    addCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        addCustomerError.classList.add('d-none');
        const submitBtn = document.getElementById('pos_add_customer_submit');
        submitBtn.disabled = true;
        fetch('{{ route("pos.customers.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: addCustomerForm.querySelector('[name="name"]').value, phone: addCustomerForm.querySelector('[name="phone"]').value, email: addCustomerForm.querySelector('[name="email"]').value, address: addCustomerForm.querySelector('[name="address"]').value })
        }).then(r => r.json().then(data => ({ ok: r.ok, data }))).then(({ ok, data }) => {
            if (ok && data.success && data.customer) {
                const sel = document.getElementById('customer_select');
                const opt = document.createElement('option');
                opt.value = data.customer.id;
                opt.textContent = data.customer.phone ? data.customer.name + ' (' + data.customer.phone + ')' : data.customer.name;
                sel.appendChild(opt);
                sel.value = data.customer.id;
                updateMeta();
                bootstrap.Modal.getInstance(addCustomerModal).hide();
            } else {
                addCustomerError.textContent = (data && (data.message || (data.errors && Object.values(data.errors).flat().join(' ')))) || 'Error';
                addCustomerError.classList.remove('d-none');
            }
        }).finally(() => { submitBtn.disabled = false; });
    });
});
</script>
@endsection

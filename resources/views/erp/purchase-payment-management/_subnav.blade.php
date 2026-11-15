@php
    $overviewActive = request()->routeIs('purchase-payment-management.index');
    $paymentsActive = request()->routeIs('purchase-payment-management.payments');
    $chequeActive = request()->routeIs('purchase-payment-management.cheques');
    $returnsActive = request()->routeIs('purchase-payment-management.returns');
@endphp
<ul class="nav nav-pills gap-2 mb-3 flex-wrap">
    <li class="nav-item">
        <a class="nav-link {{ $overviewActive ? 'active' : '' }}" href="{{ route('purchase-payment-management.index', request()->only(['purchase_no', 'supplier_id', 'payment_method', 'payment_state', 'from_date', 'to_date'])) }}">
            <i class="bi bi-grid me-1"></i> By purchase (due)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $paymentsActive ? 'active' : '' }}" href="{{ route('purchase-payment-management.payments', request()->only(['purchase_no', 'supplier_id', 'payment_method', 'payment_state', 'payment_date_from', 'payment_date_to'])) }}">
            <i class="bi bi-list-ul me-1"></i> All payments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $chequeActive ? 'active' : '' }}" href="{{ route('purchase-payment-management.cheques', request()->only(['purchase_no', 'supplier_id', 'cheque_date_from', 'cheque_date_to'])) }}">
            <i class="bi bi-calendar2-check me-1"></i> Cheque payments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $returnsActive ? 'active' : '' }}" href="{{ route('purchase-payment-management.returns', request()->only(['return_no', 'purchase_no', 'supplier_id', 'from_date', 'to_date'])) }}">
            <i class="bi bi-arrow-return-left me-1"></i> Returns
        </a>
    </li>
</ul>

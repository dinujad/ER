@php
    $pmActive = request()->routeIs('payment-management.index');
    $chqActive = request()->routeIs('payment-management.cheques');
@endphp
<ul class="nav nav-pills gap-2 mb-3 flex-wrap">
    <li class="nav-item">
        <a class="nav-link {{ $pmActive ? 'active' : '' }}" href="{{ route('payment-management.index') }}">
            <i class="bi bi-receipt me-1"></i> Order payments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $chqActive ? 'active' : '' }}" href="{{ route('payment-management.cheques') }}">
            <i class="bi bi-calendar2-check me-1"></i> Cheque Payments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('reports.show', 'pending-cheques') }}">
            <i class="bi bi-hourglass-split me-1"></i> Pending cheques report
        </a>
    </li>
</ul>

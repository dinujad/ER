@extends('erp.layout')

@section('title', 'Access Denied')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center" style="max-width: 480px;">
        <div class="mb-4">
            <i class="bi bi-lock-fill text-danger" style="font-size: 4rem;"></i>
        </div>
        <h2 class="fw-bold mb-2">Access Denied</h2>
        <p class="text-muted mb-1">You don't have permission to view this page.</p>
        <p class="text-muted small mb-4">
            {{ $exception->getMessage() ?? "Your role doesn't include access to this section." }}
        </p>

        @php $user = auth()->user(); @endphp
        @if($user)
        <div class="mb-4">
            <span class="badge bg-secondary px-3 py-2 fs-6">
                <i class="bi bi-person-badge me-1"></i>
                {{ $user->roleModel?->label ?? 'No Role' }}
            </span>
        </div>

        {{-- Show quick links to permitted areas --}}
        @php
            $links = [];
            if ($user->can_access('dashboard.view'))     $links[] = ['url' => route('erp.dashboard'),       'icon' => 'bi-speedometer2', 'label' => 'Dashboard'];
            if ($user->can_access('orders.view'))        $links[] = ['url' => route('orders.index'),         'icon' => 'bi-bag-check',    'label' => 'Orders'];
            if ($user->can_access('inventory.view'))     $links[] = ['url' => route('inventory.index'),      'icon' => 'bi-boxes',        'label' => 'Inventory'];
            if ($user->can_access('purchases.view'))     $links[] = ['url' => route('purchases.index'),      'icon' => 'bi-cart',         'label' => 'Purchases'];
            if ($user->can_access('products.view'))      $links[] = ['url' => route('products.index'),       'icon' => 'bi-tag',          'label' => 'Products'];
            if ($user->can_access('customers.view'))     $links[] = ['url' => route('erp.customers.index'),  'icon' => 'bi-people',       'label' => 'Customers'];
            if ($user->can_access('employees.view'))     $links[] = ['url' => route('employees.index'),      'icon' => 'bi-person-lines-fill', 'label' => 'Employees'];
            if ($user->can_access('hrm.view'))           $links[] = ['url' => route('hrm.index'),        'icon' => 'bi-calendar2-check', 'label' => 'HRM'];
            if ($user->can_access('expenses.view'))      $links[] = ['url' => route('expenses.index'),       'icon' => 'bi-cash-stack',   'label' => 'Expenses'];
            if ($user->can_access('reports.view'))       $links[] = ['url' => route('reports.index'),        'icon' => 'bi-bar-chart',    'label' => 'Reports'];
        @endphp

        @if(count($links))
        <p class="text-muted small mb-3">You can access these areas:</p>
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
            @foreach($links as $link)
            <a href="{{ $link['url'] }}" class="btn btn-outline-primary btn-sm">
                <i class="{{ $link['icon'] }} me-1"></i>{{ $link['label'] }}
            </a>
            @endforeach
        </div>
        @else
        <div class="alert alert-warning">
            Your role has no permissions assigned. Please contact the administrator.
        </div>
        @endif
        @endif

        <a href="javascript:history.back()" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Go Back
        </a>
    </div>
</div>
@endsection

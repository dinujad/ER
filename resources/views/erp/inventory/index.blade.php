@extends('erp.layout')

@section('content')
@php
    $activeType = $type ?? 'all';
    $totalMaterials = $materials->count();
    $totalQty = $materials->sum('quantity');
    $fabricCount = $materials->where('type', 'fabric')->count();
    $accessoryCount = $materials->where('type', 'accessory')->count();
    $lowStockCount = $materials->filter(fn ($m) => (float) $m->quantity <= 10)->count();
    $listTitle = match ($activeType) {
        'fabric' => 'Fabric Stock',
        'accessory' => 'Accessories Stock',
        default => 'Material Stock List',
    };
@endphp

<style>
    .inv-type-pills .btn {
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 0.4rem 1rem;
    }
    .inv-type-pills .btn.active-pill {
        color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
    .inv-type-pills .btn-pill-all.active-pill { background: #475569; border-color: #475569; }
    .inv-type-pills .btn-pill-fabric.active-pill { background: #3b82f6; border-color: #3b82f6; }
    .inv-type-pills .btn-pill-accessory.active-pill { background: #0ea5e9; border-color: #0ea5e9; }
</style>

<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Inventory Management</h4>
            <p class="text-muted mb-0">Track raw materials, stock levels, and quick actions from one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('inventory.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Material
            </a>
            <a href="{{ route('inventory.issue') }}" class="btn btn-warning">
                <i class="bi bi-box-arrow-up-right me-1"></i> Issue Material
            </a>
            <a href="{{ route('purchases.create') }}" class="btn btn-success">
                <i class="bi bi-cart-plus me-1"></i> Add Purchase
            </a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-people me-1"></i> Suppliers
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Materials</p>
                    <h4 class="mb-0">{{ $totalMaterials }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Quantity</p>
                    <h4 class="mb-0">{{ number_format($totalQty, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Fabric / Accessory</p>
                    <h4 class="mb-0">{{ $fabricCount }} <span class="text-muted fs-6">/ {{ $accessoryCount }}</span></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Low Stock (<=10)</p>
                    <h4 class="mb-0 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">{{ $lowStockCount }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="inv-type-pills d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-pill-all {{ $activeType === 'all' ? 'active-pill' : '' }}">All materials</a>
        <a href="{{ route('inventory.index', ['type' => 'fabric']) }}" class="btn btn-outline-primary btn-pill-fabric {{ $activeType === 'fabric' ? 'active-pill' : '' }}"><i class="bi bi-layers me-1"></i> Fabric</a>
        <a href="{{ route('inventory.index', ['type' => 'accessory']) }}" class="btn btn-outline-info btn-pill-accessory {{ $activeType === 'accessory' ? 'active-pill' : '' }}"><i class="bi bi-scissors me-1"></i> Accessories</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <h6 class="mb-0 fw-semibold">{{ $listTitle }}</h6>
            <span class="badge bg-light text-dark">{{ $totalMaterials }} items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            @php $isLowStock = (float) $material->quantity <= 10; @endphp
                            <tr>
                                <td class="ps-3 text-muted">{{ $material->id }}</td>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td>
                                    <span class="badge {{ $material->type === 'fabric' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">
                                        {{ ucfirst($material->type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $isLowStock ? 'text-danger fw-semibold' : '' }}">
                                        {{ number_format($material->quantity, 2) }}
                                    </span>
                                    @if($isLowStock)
                                        <span class="badge bg-danger-subtle text-danger ms-1">Low</span>
                                    @endif
                                </td>
                                <td>{{ $material->unit }}</td>
                                <td>Rs {{ number_format((float) ($material->unit_price ?? 0), 2) }}</td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('inventory.history', $material) }}" class="btn btn-sm btn-outline-secondary" title="Stock history">
                                        <i class="bi bi-clock-history"></i> History
                                    </a>
                                    @canRoute('inventory.edit')
<a href="{{ route('inventory.edit', $material->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
@endcanRoute
                                    @canRoute('inventory.destroy')
<form action="{{ route('inventory.destroy', $material->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete &quot;{{ addslashes($material->name) }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_type" value="{{ $activeType }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
@endcanRoute
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    @if($activeType === 'fabric')
                                        No fabric materials found.
                                    @elseif($activeType === 'accessory')
                                        No accessories found.
                                    @else
                                        No materials found. Add your first material to start tracking inventory.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(session('inventory_delete_error'))
<div class="modal fade" id="inventoryDeleteBlockedModal" tabindex="-1" aria-labelledby="inventoryDeleteBlockedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger" id="inventoryDeleteBlockedLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Cannot Delete Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">{{ session('inventory_delete_error') }}</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('inventory_delete_error'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('inventoryDeleteBlockedModal');
    if (el && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
});
</script>
@endif
@endsection

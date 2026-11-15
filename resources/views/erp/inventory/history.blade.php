@extends('erp.layout')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Stock History: {{ $material->name }}</h4>
            <p class="text-muted mb-0">
                Type: <span class="badge {{ $material->type === 'fabric' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ ucfirst($material->type) }}</span>
                &nbsp;·&nbsp; Current stock:
                <strong class="{{ (float) $material->quantity <= 10 ? 'text-danger' : '' }}">{{ number_format($material->quantity, 2) }} {{ $material->unit }}</strong>
            </p>
        </div>
        <a href="{{ route('inventory.index', ['type' => $material->type === 'fabric' ? 'fabric' : ($material->type === 'accessory' ? 'accessory' : 'all')]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Type</th>
                            <th class="text-end">Qty change</th>
                            <th class="text-end">Balance after</th>
                            <th>Reference</th>
                            <th>User</th>
                            <th class="pe-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $m->created_at->format('d M Y H:i') }}</td>
                            <td>
                                @if($m->isInbound())
                                    <span class="badge bg-success-subtle text-success">IN</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">OUT</span>
                                @endif
                                {{ \App\Models\MaterialStockMovement::typeLabel($m->movement_type) }}
                            </td>
                            <td class="text-end font-monospace fw-semibold {{ $m->isInbound() ? 'text-success' : 'text-danger' }}">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ number_format($m->quantity, 2) }}
                            </td>
                            <td class="text-end font-monospace">{{ number_format($m->balance_after, 2) }}</td>
                            <td>
                                @if($m->reference_label)
                                    <span class="fw-semibold">{{ $m->reference_label }}</span>
                                @elseif($m->reference_type && $m->reference_id)
                                    <span class="small text-muted">{{ class_basename($m->reference_type) }} #{{ $m->reference_id }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if($m->movement_type === \App\Models\MaterialStockMovement::TYPE_PURCHASE_RETURN && $m->reference_id && ($returnPurchaseRoutes[$m->reference_id] ?? null))
                                    <br><a href="{{ $returnPurchaseRoutes[$m->reference_id] }}" class="small">View PO</a>
                                @elseif($m->movement_type === \App\Models\MaterialStockMovement::TYPE_PURCHASE_IN && $m->reference_id)
                                    <br><a href="{{ route('purchases.show', $m->reference_id) }}" class="small">View PO</a>
                                @endif
                            </td>
                            <td class="small">{{ $m->creator?->name ?? '—' }}</td>
                            <td class="pe-3 small text-muted">{{ $m->notes ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No stock movements yet. Movements are recorded when you receive purchases, return to supplier, issue materials, or edit quantity.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($movements->hasPages())
            <div class="card-footer bg-white border-0">{{ $movements->links() }}</div>
        @endif
    </div>
</div>
@endsection

@extends('erp.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Stock History: {{ $product->name }}</h1>
            <p class="mb-0 text-muted">
                Wholesale: <strong class="text-primary">{{ number_format($product->wholesale_stock, 2) }}</strong>
                &nbsp;|&nbsp; Retail: <strong class="text-info">{{ number_format($product->retail_stock, 2) }}</strong>
                &nbsp;|&nbsp; Total: <strong>{{ $product->current_stock }} {{ $product->unit?->short_name ?? '' }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($product->wholesale_stock > 0)
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#historyTransferToRetailModal">
                → Retail
            </button>
            @endif
            @if($product->retail_stock > 0)
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#historyTransferToWholesaleModal">
                → Wholesale
            </button>
            @endif
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Channel</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgers as $ledger)
                            <tr>
                                <td>{{ $ledger->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge {{ ($ledger->channel ?? 'wholesale') === 'retail' ? 'bg-info text-dark' : 'bg-primary' }}">
                                        {{ ucfirst($ledger->channel ?? 'wholesale') }}
                                    </span>
                                </td>
                                <td>
                                    @if($ledger->quantity > 0)
                                        <span class="badge bg-success">IN</span> {{ ucfirst(str_replace('_', ' ', $ledger->transaction_type)) }}
                                    @else
                                        <span class="badge bg-danger">OUT</span> {{ ucfirst(str_replace('_', ' ', $ledger->transaction_type)) }}
                                    @endif
                                </td>
                                <td class="fw-bold {{ $ledger->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $ledger->quantity > 0 ? '+' : '' }}{{ $ledger->quantity }}
                                </td>
                                <td>
                                    @if($ledger->reference_type && $ledger->reference_id)
                                        <small>{{ class_basename($ledger->reference_type) }} #{{ $ledger->reference_id }}</small>
                                    @else
                                        <small>-</small>
                                    @endif
                                </td>
                                <td>{{ $ledger->creator?->name ?? 'System' }}</td>
                                <td>{{ $ledger->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $ledgers->links() }}
            </div>
        </div>
    </div>
</div>

@if($product->wholesale_stock > 0)
<div class="modal fade" id="historyTransferToRetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('products.transfer-channel-stock', $product) }}">
                @csrf
                <input type="hidden" name="direction" value="wholesale_to_retail">
                <div class="modal-header">
                    <h5 class="modal-title">Wholesale → Retail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Wholesale available: <strong>{{ number_format($product->wholesale_stock, 2) }}</strong></p>
                    @if($product->product_type === 'variable' && $product->variations->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Variation</label>
                        <select name="product_variation_id" class="form-select" required>
                            <option value="">Select variation</option>
                            @foreach($product->variations as $variation)
                                <option value="{{ $variation->id }}">{{ $variation->variation_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" max="{{ $product->wholesale_stock }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Transfer to Retail</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($product->retail_stock > 0)
<div class="modal fade" id="historyTransferToWholesaleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('products.transfer-channel-stock', $product) }}">
                @csrf
                <input type="hidden" name="direction" value="retail_to_wholesale">
                <div class="modal-header">
                    <h5 class="modal-title">Retail → Wholesale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Retail available: <strong>{{ number_format($product->retail_stock, 2) }}</strong></p>
                    @if($product->product_type === 'variable' && $product->variations->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Variation</label>
                        <select name="product_variation_id" class="form-select" required>
                            <option value="">Select variation</option>
                            @foreach($product->variations as $variation)
                                <option value="{{ $variation->id }}">{{ $variation->variation_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0.01" max="{{ $product->retail_stock }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Transfer to Wholesale</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

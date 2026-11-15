@php
    $isWholesale = $order->salesPerson && $order->salesPerson->role === 'sales_person';
    $needsManufacturing = !in_array(($workflow ?? 'all'), ['ac', 'issued']) && in_array($order->id, $manufacturingNeededOrderIds ?? [], true);
    $showIssuedCols = ($workflow ?? '') === 'issued' || $order->status === 'completed';
@endphp
<tr class="{{ $needsManufacturing ? 'manufacturing-needed-row' : '' }}">
    <td>
        <h6 class="mb-0 text-sm fw-bold text-dark">{{ $order->order_number }}</h6>
        @if($order->is_urgent)
            <span class="badge bg-danger mt-1">Urgent</span>
        @endif
        @if($showIssuedCols && $order->invoice_number)
            <small class="text-muted d-block">Invoice: {{ $order->invoice_number }}</small>
        @endif
        @if($needsManufacturing)
            <small class="text-danger fw-semibold d-block">Stock නැත / නිෂ්පාදනය අවශ්‍ය</small>
        @endif
    </td>
    <td><span class="text-muted small fw-bold">{{ $order->order_date->format('M d, Y') }}</span></td>
    <td>
        <span class="text-dark">{{ optional($order->customer)->display_name ?? 'Walk-in' }}</span>
        @if($order->customer?->business_name && $order->customer?->name && strcasecmp($order->customer->business_name, $order->customer->name) !== 0)
            <small class="text-muted d-block">{{ $order->customer->name }}</small>
        @endif
    </td>
    <td>
        <span class="s-badge s-badge-{{ $isWholesale ? 'dark' : 'info' }}">
            {{ $isWholesale ? 'Wholesale' : 'Retail' }}
        </span>
    </td>
    <td><span class="text-muted">{{ $isWholesale ? $order->salesPerson->name : 'N/A' }}</span></td>
    <td>
        @php $checkProgress = $order->orderCheckProgress(); @endphp
        @if($checkProgress['total'] === 0)
            <span class="text-muted">—</span>
        @elseif($checkProgress['left'] === 0)
            <span class="badge bg-success">Checked {{ $checkProgress['checked'] }}/{{ $checkProgress['total'] }}</span>
        @else
            <div class="d-flex flex-column gap-1">
                <span class="badge bg-success">Checked {{ $checkProgress['checked'] }}</span>
                <span class="badge bg-warning text-dark">Left {{ $checkProgress['left'] }}</span>
            </div>
        @endif
    </td>
    @if($showIssuedCols && ($workflow ?? '') === 'issued')
        <td>{{ $order->customer->display_name ?? 'N/A' }}</td>
        <td>{{ $order->issued_bags_count ?? '—' }}</td>
        <td>{{ $order->issued_bundle_count ?? '—' }}</td>
    @endif
    <td class="text-end font-monospace"><strong>{{ number_format($order->total_amount, 2) }}</strong></td>
    <td class="text-end font-monospace small">
        <span class="text-success d-block">{{ number_format($order->paid_amount, 2) }}</span>
        @if($order->due_amount > 0)
            <span class="text-danger">Due: {{ number_format($order->due_amount, 2) }}</span>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="text-end">
        <div class="d-flex flex-wrap gap-1 justify-content-end align-items-center">
            <a href="{{ route('orders.show', $order->id) }}" class="action-btn action-view" title="View"><i class="bi bi-eye"></i></a>
            @if($order->canAdminEditItems() && auth()->user()?->can_access('orders.edit'))
                @canRoute('orders.edit')
<a href="{{ route('orders.edit', $order->id) }}" class="action-btn action-view" title="Edit"><i class="bi bi-pencil-square"></i></a>
@endcanRoute
            @endif
            @if($order->status === 'afterchecking')
                <button type="button" class="action-btn action-pay" title="Issue Invoice" data-bs-toggle="modal" data-bs-target="#issueInvoiceModal{{ $order->id }}">
                    <i class="bi bi-send-check"></i>
                </button>
            @endif
            @if($order->canAcceptPayments() && $order->due_amount > 0)
                <a href="{{ route('orders.show', $order->id) }}#add-payment" class="action-btn action-pay" title="Payment"><i class="bi bi-cash-coin"></i></a>
            @endif
            @if($order->status === 'completed')
                <a href="{{ route('orders.invoice', $order->id) }}" class="action-btn action-inv" title="Invoice"><i class="bi bi-receipt"></i></a>
            @endif
            @canRoute('orders.destroy')
<form method="POST" action="{{ route('orders.destroy', $order->id) }}" class="d-inline"
                onsubmit="return confirm(@js('Delete order ' . $order->order_number . '? This cannot be undone.' . ($order->status === 'completed' ? ' Stock from this invoice will be restored.' : '')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="action-btn action-delete" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
@endcanRoute
        </div>
    </td>
    <td>
        <div class="status-move-btns d-flex flex-wrap gap-1">
            @foreach($boardStatuses as $targetStatus => $targetMeta)
                @if($targetStatus === $order->status)
                    @continue
                @endif
                @if($targetStatus === 'completed')
                    @continue
                @endif
                @canRoute('orders.update_status')
<form method="POST" action="{{ route('orders.update_status', $order->id) }}" class="d-inline"
                    @if($targetStatus === 'cancelled') onsubmit="return confirm('Cancel this order?');" @endif>
                    @csrf
                    <input type="hidden" name="status" value="{{ $targetStatus }}">
                    @foreach(request()->except('page') as $filterKey => $filterValue)
                        @if(is_scalar($filterValue) && $filterValue !== '')
                            <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                        @endif
                    @endforeach
                    <button type="submit" class="btn btn-sm btn-outline-{{ $targetMeta['badge'] }}" title="Move to {{ $targetMeta['label'] }}">
                        {{ $targetMeta['label'] }}
                    </button>
                </form>
@endcanRoute
            @endforeach
        </div>
    </td>
</tr>

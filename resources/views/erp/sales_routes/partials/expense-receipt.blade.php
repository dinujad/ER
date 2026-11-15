@php
    $slipPath = $expense->slip_path ?? null;
    $slipExt = $slipPath ? strtolower(pathinfo($slipPath, PATHINFO_EXTENSION)) : '';
    $isImage = in_array($slipExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
@endphp
@if($slipPath)
    <div class="d-flex align-items-center gap-2">
        @if($isImage)
            <a href="{{ route('field-expenses.sales-person.receipt', $expense) }}" target="_blank" rel="noopener" class="d-inline-block">
                <img src="{{ route('field-expenses.sales-person.receipt', $expense) }}" alt="Receipt"
                    class="rounded border" style="height:56px; width:72px; object-fit:cover;">
            </a>
        @endif
        <a href="{{ route('field-expenses.sales-person.receipt', $expense) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-receipt"></i> View
        </a>
    </div>
@else
    <span class="text-muted">No receipt</span>
@endif

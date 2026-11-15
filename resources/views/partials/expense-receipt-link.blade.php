@if(!empty($expense) && $expense->slip_path)
    <a href="{{ route('field-expenses.sales-person.receipt', $expense) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-0">
        <i class="bi bi-receipt"></i> Receipt
    </a>
@elseif(!empty($item) && $item->slip_path)
    <a href="{{ route('field-expenses.driver.receipt', $item) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-0">
        <i class="bi bi-receipt"></i> Receipt
    </a>
@else
    <span class="text-muted">—</span>
@endif

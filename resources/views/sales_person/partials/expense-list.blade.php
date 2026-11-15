{{-- @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator $expenses --}}
{{-- @var string|null $redirectTo --}}
<div class="card section-card">
    <div class="card-header">
        <span><i class="bi bi-list-check"></i> {{ $listTitle ?? 'Your expenses' }}</span>
        @if(isset($monthTotal))
            <strong class="text-primary">Rs {{ number_format($monthTotal, 2) }}</strong>
        @endif
    </div>
    <div class="card-body p-0">
        @if($expenses->isEmpty())
            <p class="text-muted small p-4 mb-0 text-center">No expenses recorded yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th>Slip</th>
                            @if($showActions ?? true)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                            <tr>
                                <td class="text-nowrap">{{ optional($expense->expense_date)->format('d M Y') ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $expense->typeLabel() }}</span></td>
                                <td>{{ $expense->description }}</td>
                                <td class="text-end font-monospace">{{ number_format($expense->amount, 2) }}</td>
                                <td>
                                    @include('partials.expense-receipt-link', ['expense' => $expense])
                                </td>
                                @if($showActions ?? true)
                                    <td class="text-end">
                                        @canRoute('sales-person.expenses.destroy')
<form action="{{ route('sales-person.expenses.destroy', $expense) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Remove this expense?');">
                                            @csrf
                                            @method('DELETE')
                                            @if(!empty($redirectTo))
                                                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                                            @endif
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0">Remove</button>
                                        </form>
@endcanRoute
                                    </td>
                                @endif
                            </tr>
                            @if($expense->notes)
                                <tr class="table-light">
                                    <td colspan="{{ ($showActions ?? true) ? 6 : 5 }}" class="small text-muted py-1 ps-4">{{ $expense->notes }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(method_exists($expenses, 'links'))
                <div class="p-2 border-top">{{ $expenses->links() }}</div>
            @endif
        @endif
    </div>
</div>

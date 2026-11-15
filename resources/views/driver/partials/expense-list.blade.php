{{-- @var \App\Models\VehicleDailyRun $run --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Today's expenses</span>
        <strong class="text-primary">Rs {{ number_format($run->items->sum('cost'), 2) }}</strong>
    </div>
    <div class="card-body p-0">
        @if($run->items->isEmpty())
            <p class="text-muted small p-3 mb-0">No expenses yet. Use the form above to add fuel, toll, or other costs.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th>Slip</th>
                            @if($run->status === 'started')
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($run->items as $item)
                            <tr>
                                <td>{{ ucfirst($item->type) }}</td>
                                <td>{{ $item->name }}</td>
                                <td class="text-end font-monospace">{{ number_format($item->cost, 2) }}</td>
                                <td>
                                    @include('partials.expense-receipt-link', ['item' => $item])
                                </td>
                                @if($run->status === 'started')
                                    <td class="text-end">
                                        @canRoute('driver.delete-item')
<form action="{{ route('driver.delete-item', $item) }}" method="POST" class="d-inline"
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@extends('accounting::phase2._page')

@php
    $pageTitle = 'Payment Runs'; $pageSub = 'Batches of supplier payments — prepare, approve, send to the bank, confirm';
    $badges = ['draft' => 'bg-secondary', 'approved' => 'bg-primary', 'exported' => 'bg-info text-dark', 'completed' => 'bg-success', 'cancelled' => 'bg-light text-muted border'];
@endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/bills/payment-run') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New payment run</a>
@endsection

@section('page_body')
<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light"><tr><th>Run</th><th>Payment date</th><th class="text-end">Bills</th><th class="text-end">Total</th><th>Status</th><th>Created</th><th></th></tr></thead>
    <tbody>
    @forelse($runs as $r)
        @php $count = \Illuminate\Support\Facades\DB::table('acct_payment_run_items')->where('payment_run_id', $r->id)->whereIn('status', ['pending', 'paid'])->count(); @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ url('accounting/phase7/payment-runs/' . $r->id) }}'">
            <td><a href="{{ url('accounting/phase7/payment-runs/' . $r->id) }}" class="fw-semibold">{{ $r->run_no }}</a></td>
            <td>{{ $r->payment_date }}</td>
            <td class="text-end">{{ $count }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $r->total, 2) }}</td>
            <td><span class="badge {{ $badges[$r->status] ?? 'bg-secondary' }}">{{ ucfirst($r->status) }}</span></td>
            <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('Y-m-d H:i') }}</td>
            <td class="text-end"><a href="{{ url('accounting/phase7/payment-runs/' . $r->id) }}" class="btn btn-outline-primary btn-xs">Open <i class="bi bi-chevron-right"></i></a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted">No payment runs yet. Start one from <a href="{{ url('accounting/phase6/bills/payment-run') }}">Pay Bills</a>.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $runs->links() }}
@endsection

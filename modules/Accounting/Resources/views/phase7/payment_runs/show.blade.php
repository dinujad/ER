@extends('accounting::phase2._page')

@php
    $pageTitle = 'Payment run ' . $run->run_no;
    $pageSub = 'Pay on ' . $run->payment_date . ($bank ? ' from ' . $bank->gl_code . ' ' . $bank->name : '');
    $badges = ['draft' => 'bg-secondary', 'approved' => 'bg-primary', 'exported' => 'bg-info text-dark', 'completed' => 'bg-success', 'cancelled' => 'bg-light text-muted border'];
    $itemBadges = ['pending' => 'bg-warning text-dark', 'paid' => 'bg-success', 'failed' => 'bg-danger', 'removed' => 'bg-light text-muted border'];
    $active = in_array($run->status, ['draft', 'approved', 'exported'], true);
    $steps = ['draft' => 'Prepared', 'approved' => 'Approved', 'exported' => 'Sent to bank', 'completed' => 'Confirmed'];
    $order = array_keys($steps);
    $reached = array_search($run->status, $order, true);
@endphp

@section('page_actions')
    <a href="{{ url('accounting/phase7/payment-runs') }}" class="btn btn-outline-secondary btn-sm">All runs</a>
    @if($active && !\Illuminate\Support\Facades\DB::table('acct_payment_run_items')->where('payment_run_id', $run->id)->where('status', 'paid')->exists())
        <form method="POST" action="{{ url('accounting/phase7/payment-runs/' . $run->id . '/cancel') }}" onsubmit="return confirm('Cancel this payment run? Bills go back to unpaid.');">@csrf<button class="btn btn-outline-danger btn-sm">Cancel run</button></form>
    @endif
@endsection

@section('page_body')
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

{{-- Progress --}}
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <span class="badge {{ $badges[$run->status] ?? 'bg-secondary' }} fs-6">{{ ucfirst($run->status) }}</span>
    @foreach($steps as $key => $label)
        @php $done = $reached !== false && array_search($key, $order, true) <= $reached; @endphp
        <span class="small {{ $done ? 'text-success fw-semibold' : 'text-muted' }}"><i class="bi {{ $done ? 'bi-check-circle-fill' : 'bi-circle' }}"></i> {{ $label }}</span>
        @if(!$loop->last)<i class="bi bi-chevron-right text-muted small"></i>@endif
    @endforeach
</div>

{{-- Next action --}}
<div class="card mb-3"><div class="card-body">
    @if($run->status === 'draft')
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>Check the bills and amounts below, then approve. @if(config('dermas.payment_run_four_eyes'))<span class="text-muted small">(must be approved by someone other than the preparer)</span>@endif</div>
            <form method="POST" action="{{ url('accounting/phase7/payment-runs/' . $run->id . '/approve') }}">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-check2-circle"></i> Approve run (Rs {{ number_format((float) $run->total, 2) }})</button></form>
        </div>
    @elseif(in_array($run->status, ['approved', 'exported'], true))
        <div class="row g-3">
            <div class="col-md-6">
                <div class="fw-semibold mb-1">1. Bank file</div>
                <form method="GET" action="{{ url('accounting/phase7/payment-runs/' . $run->id . '/export') }}" class="d-flex gap-2">
                    <select name="format" class="form-select form-select-sm">
                        @foreach($formats as $k => $label)<option value="{{ $k }}" @selected($run->export_format === $k)>{{ $label }}</option>@endforeach
                    </select>
                    <button class="btn btn-outline-primary btn-sm text-nowrap"><i class="bi bi-download"></i> Download</button>
                </form>
                @if($run->exported_at)<div class="small text-muted mt-1">Last downloaded {{ \Illuminate\Support\Carbon::parse($run->exported_at)->format('Y-m-d H:i') }} ({{ $run->export_format }})</div>@endif
            </div>
            <div class="col-md-6">
                <div class="fw-semibold mb-1">2. After the bank confirms</div>
                <form method="POST" action="{{ url('accounting/phase7/payment-runs/' . $run->id . '/confirm') }}" class="d-flex gap-2" onsubmit="return confirm('Record a supplier payment for every bill still pending in this run?');">@csrf
                    <input type="date" name="paid_on" value="{{ $run->payment_date }}" class="form-control form-control-sm">
                    <button class="btn btn-success btn-sm text-nowrap"><i class="bi bi-check-all"></i> Mark pending as paid</button>
                </form>
                <div class="small text-muted mt-1">Mark any transfer the bank rejected as <em>failed</em> first — failed bills stay unpaid. Confirming twice never pays a bill twice.</div>
            </div>
        </div>
    @elseif($run->status === 'completed')
        <div class="text-success"><i class="bi bi-check-circle"></i> Completed {{ $run->completed_at ? \Illuminate\Support\Carbon::parse($run->completed_at)->format('Y-m-d H:i') : '' }}. Supplier payments are posted; match them in <a href="{{ url('accounting/phase7/reconcile') }}">Reconcile</a> when the bank statement arrives.</div>
    @else
        <div class="text-muted">This run was cancelled.</div>
    @endif
</div></div>

<div class="card mb-3"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Supplier</th><th>Bank account</th><th>Bill</th><th>Due</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($items as $i)
        <tr class="{{ in_array($i->status, ['failed', 'removed'], true) ? 'text-muted' : '' }}">
            <td>{{ $i->supplier_name }}</td>
            <td class="small">@if($i->bank_account_no){{ $i->bank_name }} · {{ $i->bank_account_no }}@else<span class="text-danger">missing</span>@endif</td>
            <td><a href="{{ url('accounting/phase6/bills/' . $i->purchase_id) }}">{{ $i->purchase_no }}</a> <span class="small text-muted">{{ $i->reference_no }}</span></td>
            <td class="small">{{ $i->due_date }}</td>
            <td class="text-end font-monospace">{{ number_format((float) $i->amount, 2) }}</td>
            <td><span class="badge {{ $itemBadges[$i->status] ?? 'bg-secondary' }}">{{ ucfirst($i->status) }}</span>@if($i->failure_reason)<div class="small text-danger">{{ $i->failure_reason }}</div>@endif</td>
            <td class="text-end">
                @if($i->status === 'pending' && $active)
                    <form method="POST" action="{{ url('accounting/phase7/payment-runs/' . $run->id . '/items/' . $i->id . '/fail') }}" class="d-inline" onsubmit="var r = prompt('Why did this transfer fail?'); if (!r) return false; this.reason.value = r; return true;">@csrf
                        <input type="hidden" name="reason"><button class="btn btn-outline-danger btn-xs"><i class="bi bi-x-circle"></i> Mark failed</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot><tr class="fw-bold"><td colspan="4" class="text-end">Total to pay</td><td class="text-end font-monospace">{{ number_format((float) $run->total, 2) }}</td><td colspan="2"></td></tr></tfoot>
</table>
</div></div></div>

<div class="card"><div class="card-header"><strong>History</strong></div><div class="card-body">
    @include('accounting::phase6.partials.history', ['logs' => $history])
</div></div>
@endsection

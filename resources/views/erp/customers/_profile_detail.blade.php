@php
    $profileService = app(\App\Services\WholesaleCustomerProfileService::class);
    $isSalesPerson = ($profileContext ?? 'erp') === 'sales_person';
    $orderShowRoute = $isSalesPerson ? 'sales-person.orders.show' : 'orders.show';
    $orderPaymentRoute = $isSalesPerson ? 'sales-person.orders.show' : 'orders.show';
    $paymentAnchor = '#add-payment';
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small text-uppercase">Orders</div>
                <div class="fs-4 fw-bold text-primary">{{ $orderCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small text-uppercase">Total purchased</div>
                <div class="fs-5 fw-bold">Rs {{ number_format($totalOrdered, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small text-uppercase">Total paid</div>
                <div class="fs-5 fw-bold text-success">Rs {{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm h-100 border-danger-subtle">
            <div class="card-body text-center py-3">
                <div class="text-muted small text-uppercase">Balance due</div>
                <div class="fs-5 fw-bold {{ $totalDue > 0 ? 'text-danger' : 'text-muted' }}">Rs {{ number_format($totalDue, 2) }}</div>
                @if(($customer->credit_limit ?? 0) > 0)
                    <small class="text-muted">Credit limit: Rs {{ number_format($customer->credit_limit, 2) }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="card border-0 shadow-sm h-100 border-warning">
            <div class="card-body text-center py-3">
                <div class="text-muted small text-uppercase">Pending cheque</div>
                <div class="fs-5 fw-bold {{ ($pendingChequeAmount ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                    Rs {{ number_format($pendingChequeAmount ?? 0, 2) }}
                </div>
                <small class="text-muted d-block">
                    Cheque amount only — not deposited to bank yet
                    @if(($pendingChequeCount ?? 0) > 0)
                        ({{ $pendingChequeCount }} cheque{{ $pendingChequeCount === 1 ? '' : 's' }})
                    @endif
                </small>
                @if(!$isSalesPerson && ($pendingChequeAmount ?? 0) > 0)
                    <a href="{{ route('reports.show', ['report' => 'pending-cheques', 'customer_id' => $customer->id]) }}" class="btn btn-sm btn-outline-warning mt-2">
                        View pending cheques
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="card border-0 shadow-sm h-100 border-info">
            <div class="card-body text-center py-3">
                <div class="text-muted small text-uppercase">Full return credit</div>
                <div class="fs-5 fw-bold {{ (($returnCredit['full'] ?? 0) > 0) ? 'text-info' : 'text-muted' }}">
                    Rs {{ number_format($returnCredit['full'] ?? 0, 2) }}
                </div>
                <small class="text-muted d-block">
                    Previous Rs {{ number_format($returnCredit['previous'] ?? 0, 2) }}
                    · System Rs {{ number_format($returnCredit['system'] ?? 0, 2) }}
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <strong>Filter order history</strong>
    </div>
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            @if($isSalesPerson)
                {{-- keep same URL --}}
            @else
                {{-- erp show uses customer in URL; form submits to same page --}}
            @endif
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">Order number</label>
                <input type="text" name="order_no" class="form-control form-control-sm" value="{{ request('order_no') }}" placeholder="ORD…">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="order_status" class="form-select form-select-sm">
                    <option value="all">All statuses</option>
                    @foreach(['pending', 'processing', 'afterchecking', 'completed', 'cancelled'] as $st)
                        <option value="{{ $st }}" {{ request('order_status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-bag-check me-1"></i> Purchase / order history</strong>
        <span class="badge bg-light text-dark border">{{ $orders->count() }} row(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Sales person</th>
                        <th>Status</th>
                        <th class="text-end">Total (Rs)</th>
                        <th class="text-end">Paid (Rs)</th>
                        <th class="text-end">Due (Rs)</th>
                        <th>Payments</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $paid = $profileService->orderPaidAmount($order);
                            $due = $profileService->orderDueAmount($order);
                            $isCancelled = $order->status === 'cancelled';
                        @endphp
                        <tr class="{{ $isCancelled ? 'table-secondary text-muted' : ($due > 0 ? 'table-warning' : '') }}">
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td class="small">{{ ($order->order_date ?? $order->created_at)?->format('Y-m-d') }}</td>
                            <td class="small">{{ ($order->salesPerson && $order->salesPerson->role === 'sales_person') ? $order->salesPerson->name : '—' }}</td>
                            <td><span class="badge bg-{{ $isCancelled ? 'danger' : ($order->status === 'completed' ? 'success' : 'secondary') }}">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end font-monospace">{{ number_format($order->invoice_total, 2) }}</td>
                            <td class="text-end font-monospace text-success">{{ number_format($paid, 2) }}</td>
                            <td class="text-end font-monospace {{ $due > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($due, 2) }}</td>
                            <td class="small">{{ $order->payments->count() }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route($orderShowRoute, $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if(!$isCancelled && $due > 0 && $order->canAcceptPayments() && !$isSalesPerson)
                                    <a href="{{ route($orderPaymentRoute, $order) }}{{ $paymentAnchor }}" class="btn btn-sm btn-success">Pay</a>
                                @elseif(!$isCancelled && $due > 0 && $order->canAcceptPayments() && $isSalesPerson)
                                    <a href="{{ route($orderPaymentRoute, $order) }}{{ $paymentAnchor }}" class="btn btn-sm btn-success">Pay</a>
                                @endif
                            </td>
                        </tr>
                        @if($order->payments->isNotEmpty())
                        <tr class="bg-light">
                            <td colspan="9" class="py-2 px-3">
                                <div class="small fw-semibold text-muted mb-1">Payments for {{ $order->order_number }}</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 bg-white">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Method</th>
                                                <th>Cheque date</th>
                                                <th>Reference</th>
                                                <th class="text-end">Amount (Rs)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                                <td>
                                                    {{ \App\Services\WholesaleCustomerProfileService::paymentMethodLabel($payment->payment_method) }}
                                                    @if($payment->isCheque())
                                                        <span class="badge {{ $payment->chequeStatusBadgeClass() }} ms-1">{{ $payment->chequeStatusLabel() }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ strtolower($payment->payment_method) === 'cheque' && $payment->cheque_date ? $payment->cheque_date->format('Y-m-d') : '—' }}</td>
                                                <td>{{ $payment->reference_number ?? '—' }}</td>
                                                <td class="text-end font-monospace">{{ number_format((float) $payment->amount, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No orders for this customer yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(($pendingCheques ?? collect())->isNotEmpty())
<div class="card shadow-sm border-warning mb-4">
    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-hourglass-split me-1"></i> Pending cheques (not deposited to bank)</strong>
        <span class="badge bg-warning text-dark">Rs {{ number_format($pendingChequeAmount, 2) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cheque date</th>
                        <th>Received</th>
                        <th>Order #</th>
                        <th>Reference / No.</th>
                        <th>Status</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingCheques as $row)
                    <tr>
                        <td>{{ $row->payment->cheque_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="small">{{ $row->payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            <a href="{{ route($orderShowRoute, $row->order) }}" class="fw-semibold text-decoration-none">{{ $row->order->order_number }}</a>
                        </td>
                        <td class="small">
                            {{ $row->payment->cheque_number ?: ($row->payment->reference_number ?? '—') }}
                            @if($row->payment->cheque_bank)
                                <span class="text-muted">· {{ $row->payment->cheque_bank }}</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $row->payment->chequeStatusBadgeClass() }}">{{ $row->payment->chequeStatusLabel() }}</span></td>
                        <td class="text-end font-monospace fw-semibold text-warning">{{ number_format((float) $row->payment->amount, 2) }}</td>
                        <td class="text-end">
                            @if(!$isSalesPerson)
                                <a href="{{ route('payment-management.cheques', ['customer_id' => $customer->id, 'cheque_status' => 'received']) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($paymentLedger->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <strong><i class="bi bi-cash-stack me-1"></i> All payments (newest first)</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Payment date</th>
                        <th>Order #</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Cheque date</th>
                        <th>Reference</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th class="text-end">Order due after</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentLedger as $row)
                    @php
                        $orderDue = $profileService->orderDueAmount($row->order);
                    @endphp
                    <tr>
                        <td>{{ $row->payment->payment_date?->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route($orderShowRoute, $row->order) }}" class="fw-semibold text-decoration-none">{{ $row->order->order_number }}</a>
                        </td>
                        <td>{{ \App\Services\WholesaleCustomerProfileService::paymentMethodLabel($row->payment->payment_method) }}</td>
                        <td>
                            @if($row->payment->isCheque())
                                <span class="badge {{ $row->payment->chequeStatusBadgeClass() }}">{{ $row->payment->chequeStatusLabel() }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if(strtolower($row->payment->payment_method) === 'cheque' && $row->payment->cheque_date)
                                {{ $row->payment->cheque_date->format('Y-m-d') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="small">{{ $row->payment->reference_number ?? '—' }}</td>
                        <td class="text-end font-monospace text-success">
                            {{ number_format((float) $row->payment->amount, 2) }}
                            @if($row->payment->cashNextPaymentDiscountLine())
                                <div class="small text-success text-start">{{ $row->payment->cashNextPaymentDiscountLine() }}</div>
                            @endif
                        </td>
                        <td class="text-end font-monospace {{ $orderDue > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($orderDue, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@php
    $previousReturnRoute = $isSalesPerson
        ? route('sales-person.customers.previous-return.store', $customer)
        : route('erp.customers.previous-return.store', $customer);
@endphp
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white"><strong>Return credit we owe this business</strong></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">පරණ sale වලින් දෙන්න තියෙන ගාන</div>
                    <div class="fs-5 fw-bold">Rs {{ number_format($returnCredit['previous'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">System return වලින් දෙන්න තියෙන ගාන</div>
                    <div class="fs-5 fw-bold">Rs {{ number_format($returnCredit['system'] ?? 0, 2) }}</div>
                    <div class="small text-muted">System return එකෙන් 30% ක් claim කරන්න පුළුවන්.</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small text-muted">මුළු ගාන</div>
                    <div class="fs-5 fw-bold text-primary">Rs {{ number_format($returnCredit['full'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ $previousReturnRoute }}" class="row g-2 align-items-end" onsubmit="return confirm('Add this previous return credit?');">
            @csrf
            <div class="col-md-3">
                <label class="form-label small mb-1">Previous return amount (Rs)</label>
                <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" value="{{ old('amount') }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Note (optional)</label>
                <input type="text" name="note" class="form-control form-control-sm" maxlength="500" value="{{ old('note') }}" placeholder="Old sale / return reference">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-sm btn-primary">පරණ return එකතු කරන්න</button>
            </div>
        </form>
        @if(($previousReturnCredits ?? collect())->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Added by</th>
                            <th>Note</th>
                            <th class="text-end">Added</th>
                            <th class="text-end">Claimed</th>
                            <th class="text-end">Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previousReturnCredits as $credit)
                            <tr>
                                <td>{{ $credit->created_at?->format('Y-m-d') }}</td>
                                <td>{{ $credit->creator?->name ?? '—' }}</td>
                                <td>{{ $credit->note ?: '—' }}</td>
                                <td class="text-end">{{ number_format((float) $credit->amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $credit->claimed_amount, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($credit->remaining(), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong>Sale returns &amp; claim credit</strong>
        <span class="small text-muted">Return × 30% claimable · other invoice max 30%</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Return</th>
                        <th>Date</th>
                        <th>From invoice</th>
                        <th class="text-end">Returned</th>
                        <th class="text-end">Claimable</th>
                        <th class="text-end">Claimed</th>
                        <th class="text-end">Left</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($saleReturns ?? collect()) as $ret)
                        <tr>
                            <td class="fw-semibold">{{ $ret->return_no }}</td>
                            <td>{{ $ret->return_date->format('Y-m-d') }}</td>
                            <td>
                                @if($ret->order)
                                    <a href="{{ route($orderShowRoute, $ret->order) }}">{{ $ret->order->invoice_number ?: $ret->order->order_number }}</a>
                                @else — @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $ret->total, 2) }}</td>
                            <td class="text-end text-success">{{ number_format((float) $ret->claimable_amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $ret->claimed_amount, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($ret->remainingClaimable(), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No sale returns for this customer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(($claimsOnOrders ?? collect())->isNotEmpty() || ($previousReturnClaims ?? collect())->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white"><strong>Claims applied to other invoices</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>From return</th>
                    <th>Applied to invoice</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($previousReturnClaims ?? [] as $claim)
                    <tr>
                        <td>{{ $claim->created_at?->format('Y-m-d') }}</td>
                        <td>Previous return</td>
                        <td>
                            @if($claim->order)
                                <a href="{{ route($orderShowRoute, $claim->order) }}">{{ $claim->order->invoice_number ?: $claim->order->order_number }}</a>
                            @else — @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $claim->amount, 2) }}</td>
                    </tr>
                @endforeach
                @foreach($claimsOnOrders as $claim)
                    <tr>
                        <td>{{ $claim->created_at?->format('Y-m-d') }}</td>
                        <td>{{ $claim->saleReturn?->return_no ?? '—' }}</td>
                        <td>
                            @if($claim->order)
                                <a href="{{ route($orderShowRoute, $claim->order) }}">{{ $claim->order->invoice_number ?: $claim->order->order_number }}</a>
                            @else — @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $claim->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

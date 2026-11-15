@if($payment->isCheque())
    <span class="badge {{ $payment->chequeStatusBadgeClass() }}">{{ $payment->chequeStatusLabel() }}</span>
    @if($payment->cheque_status === \App\Models\Payment::CHEQUE_BOUNCED && $payment->bounce_reason)
        <p class="small text-danger mb-0 mt-1">{{ $payment->bounce_reason }}</p>
    @endif

    @if(empty($readOnly))
    <div class="d-flex flex-wrap gap-1 mt-1">
        @if($payment->canMarkDeposited())
            <form method="POST" action="{{ route('order-payments.cheque.deposit', $payment) }}" class="d-inline"
                onsubmit="return confirm('Mark this cheque as deposited to bank?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary py-0">Deposit</button>
            </form>
        @endif
        @if($payment->canMarkCleared())
            <form method="POST" action="{{ route('order-payments.cheque.clear', $payment) }}" class="d-inline"
                onsubmit="return confirm('Mark as cleared? Funds will move from PDC to Bank in accounts.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success py-0">Clear</button>
            </form>
        @endif
        @if($payment->canMarkBounced())
            <button type="button" class="btn btn-sm btn-outline-danger py-0"
                data-bs-toggle="modal" data-bs-target="#bounceModal{{ $payment->id }}">Return</button>
        @endif
    </div>
    @endif

    @if(empty($readOnly) && $payment->canMarkBounced())
    <div class="modal fade" id="bounceModal{{ $payment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('order-payments.cheque.bounce', $payment) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Mark Cheque as Returned / Bounced</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small">
                            Order <strong>{{ $payment->order?->order_number }}</strong> will show this amount as <strong>due again</strong>.
                            Customer and sales person will be notified automatically.
                        </div>
                        <label class="form-label">Reason for return <span class="text-danger">*</span></label>
                        <textarea name="bounce_reason" class="form-control" rows="3" required minlength="5"
                            placeholder="e.g. Insufficient funds, signature mismatch, wrong date…"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Bounce</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@else
    <span class="text-muted">—</span>
@endif

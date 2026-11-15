{{-- @var \App\Models\Order $order --}}
{{-- @var string $formAction --}}
<div class="col-md-4" id="add-payment">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Add Payment</h5>
        </div>
        <div class="card-body">
            @if($order->due_amount <= 0)
                <div class="alert alert-success mb-0">Order is fully paid.</div>
            @elseif(!$order->canAcceptPayments())
                <div class="alert alert-warning mb-0">
                    <strong>Payment තවම ලබාගත නොහැක.</strong>
                    <p class="small mb-0 mt-2">
                        Order Check complete කරලා order එක <strong>After Checking</strong> වෙත යවා ඇති විට පමණක් payment add කරන්න පුළුවන්.
                        @if(!empty($isAdminView) && !in_array($order->status, ['afterchecking', 'completed', 'cancelled'], true))
                            <span class="d-block mt-2">වම් පැත්තේ <strong>Order Check</strong> button එක use කරන්න.</span>
                        @endif
                    </p>
                </div>
            @else
                @include('erp.sales_persons.orders.partials.payment-form-multi', [
                    'formAction' => $formAction,
                ])
            @endif
        </div>
    </div>
    @if(!empty($showUsageGuide))
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#orderUsageGuideModal">
                <i class="bi bi-info-circle me-1"></i> භාවිතයට උපදෙස්
            </button>
        </div>
    @endif
</div>

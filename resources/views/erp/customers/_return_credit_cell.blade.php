<td class="small">
    <div>Previous: Rs {{ number_format($customer->previousReturnRemaining(), 2) }}</div>
    <div>System: Rs {{ number_format($customer->systemReturnRemaining(), 2) }}</div>
    <div class="fw-semibold">Full: Rs {{ number_format($customer->fullReturnRemaining(), 2) }}</div>
    <button type="button"
            class="btn btn-link btn-sm p-0"
            data-bs-toggle="modal"
            data-bs-target="#previousReturnModal"
            data-action="{{ $previousReturnAction }}"
            data-business="{{ $customer->display_name }}">
        පරණ return එකතු කරන්න
    </button>
</td>

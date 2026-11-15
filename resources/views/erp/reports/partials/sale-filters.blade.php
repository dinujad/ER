@php
    $f = $data['filters'] ?? [];
@endphp
<div class="card border mb-3 no-print">
    <div class="card-body py-3">
        <form action="{{ route('reports.show', 'sale') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">Sale type</label>
                <select name="sale_type" class="form-select form-select-sm">
                    <option value="all" {{ ($f['sale_type'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="wholesale" {{ ($f['sale_type'] ?? '') === 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                    <option value="retail" {{ ($f['sale_type'] ?? '') === 'retail' ? 'selected' : '' }}>Retail</option>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">Sales person</label>
                <select name="sales_person_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($data['salesPersons'] ?? [] as $sp)
                        <option value="{{ $sp->id }}" {{ (string) ($f['sales_person_id'] ?? '') === (string) $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">Business</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($data['customers'] ?? [] as $customer)
                        <option value="{{ $customer->id }}" {{ (string) ($f['customer_id'] ?? '') === (string) $customer->id ? 'selected' : '' }}>{{ filled($customer->business_name) ? $customer->business_name : $customer->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'afterchecking' => 'After Checking', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $lbl)
                        <option value="{{ $val }}" {{ ($f['status'] ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label form-label-sm mb-1">Payment</label>
                <select name="payment_state" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="paid" {{ ($f['payment_state'] ?? '') === 'paid' ? 'selected' : '' }}>Fully paid</option>
                    <option value="due" {{ ($f['payment_state'] ?? '') === 'due' ? 'selected' : '' }}>Has due</option>
                </select>
            </div>
            <div class="col-md-2 col-6 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Apply filters</button>
                <a href="{{ route('reports.show', 'sale') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

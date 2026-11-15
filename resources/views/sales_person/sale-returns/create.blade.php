@extends('sales_person.layout')

@section('title', 'New Sale Return')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-0">New Sale Return</h1>
            <p class="text-muted small mb-0">Order/invoice number, or customer + SKU / barcode / name.</p>
        </div>
        <a href="{{ route('sales-person.sale-returns.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sales-person.sale-returns.create') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Order / Invoice No</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Find</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-semibold">Find by design (SKU / barcode / name)</div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select id="return-lookup-customer" class="form-select">
                        <option value="">Select…</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">SKU / barcode / name</label>
                    <input type="text" id="return-lookup-q" class="form-control" placeholder="Min 2 characters">
                </div>
                <div class="col-md-3">
                    <button type="button" id="return-lookup-btn" class="btn btn-outline-primary w-100">Find last invoice</button>
                </div>
            </div>
            <div id="return-lookup-status" class="small text-muted mt-2"></div>
            <div id="return-lookup-results" class="table-responsive mt-2 d-none">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Product</th>
                            <th class="text-end">Effective</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="return-lookup-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    @if(request()->filled('q') && !$order)
        <div class="alert alert-warning">No matching sale found.</div>
    @endif

    @if($order)
        @if(!$order->canAcceptSaleReturn())
            <div class="alert alert-danger">Order status must be afterchecking / completed / paid / credit.</div>
        @elseif($order->items->every(fn ($i) => $i->returnableQuantity() <= 0))
            <div class="alert alert-info">All items already returned.</div>
        @else
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <strong>{{ $order->invoice_number ?: $order->order_number }}</strong>
                    · {{ $order->customer?->display_name }}
                    <span class="badge bg-light text-dark border ms-1">{{ $order->status }}</span>
                </div>
                <div class="card-body">
                    @include('erp.sale-returns._form', [
                        'order' => $order,
                        'formAction' => route('sales-person.sale-returns.store'),
                        'hiddenFields' => [
                            'order_id' => $order->id,
                            'redirect_sales_person' => 1,
                        ],
                    ])
                </div>
                <div class="card-footer text-end">
                    <button type="submit" form="sale-return-form-{{ $order->id }}" class="btn btn-warning">Submit Sale Return</button>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const lookupUrl = @json($lookupUrl);
    const createUrl = @json($createUrl);
    const btn = document.getElementById('return-lookup-btn');
    const customerEl = document.getElementById('return-lookup-customer');
    const qEl = document.getElementById('return-lookup-q');
    const statusEl = document.getElementById('return-lookup-status');
    const wrap = document.getElementById('return-lookup-results');
    const body = document.getElementById('return-lookup-body');
    if (!btn) return;

    function money(n) {
        return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    btn.addEventListener('click', function () {
        const customerId = customerEl.value;
        const q = (qEl.value || '').trim();
        statusEl.textContent = '';
        wrap.classList.add('d-none');
        body.innerHTML = '';
        if (!customerId) { statusEl.textContent = 'Select a customer.'; return; }
        if (q.length < 2) { statusEl.textContent = 'Type at least 2 characters.'; return; }
        statusEl.textContent = 'Searching…';
        fetch(lookupUrl + '?customer_id=' + encodeURIComponent(customerId) + '&q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const rows = data.matches || [];
                if (!rows.length) {
                    statusEl.textContent = 'No returnable invoice found.';
                    return;
                }
                statusEl.textContent = rows.length + ' match(es).';
                rows.forEach(function (row) {
                    const tr = document.createElement('tr');
                    const inv = row.invoice_number || row.order_number || ('#' + row.order_id);
                    const label = (row.product || '') + (row.variation ? ' (' + row.variation + ')' : '');
                    tr.innerHTML =
                        '<td class="fw-semibold">' + inv + '</td>' +
                        '<td>' + (row.order_date || '—') + '</td>' +
                        '<td>' + label + '<div class="small text-muted">Max ' + money(row.returnable_qty) + '</div></td>' +
                        '<td class="text-end fw-semibold">' + money(row.effective_unit_price) + '</td>' +
                        '<td class="text-end"><a class="btn btn-sm btn-primary" href="' + createUrl + '?order_id=' + row.order_id + '">Open</a></td>';
                    body.appendChild(tr);
                });
                wrap.classList.remove('d-none');
            })
            .catch(function () { statusEl.textContent = 'Lookup failed.'; });
    });
})();
</script>
@endpush

@extends('erp.layout')

@section('title', 'New Sale Return')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">New Sale Return</h1>
            <p class="text-muted small mb-0">Find by order/invoice number, or by customer + SKU / barcode / name (last invoice).</p>
        </div>
        <a href="{{ route('sale-returns.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sale-returns.create') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Order / Invoice No</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="e.g. ORD106 or 26JUL-DERM-1001">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Find sale</button>
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
                        <option value="">Select customer…</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected((string) request('customer_id') === (string) $c->id)>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">SKU / barcode / name</label>
                    <input type="text" id="return-lookup-q" class="form-control" placeholder="Type at least 2 characters" value="{{ request('design_q') }}">
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
                            <th class="text-end">Max</th>
                            <th class="text-end">List</th>
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
        <div class="alert alert-warning">No matching sale found for “{{ request('q') }}”.</div>
    @endif

    @if($order)
        @if(!$order->canAcceptSaleReturn())
            <div class="alert alert-danger">
                Order {{ $order->order_number }} status is <strong>{{ $order->status }}</strong> — only afterchecking / completed / paid / credit sales can be returned.
            </div>
        @elseif($order->items->every(fn ($i) => $i->returnableQuantity() <= 0))
            <div class="alert alert-info">All items on this sale are already fully returned.</div>
        @else
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between">
                    <div>
                        <strong>{{ $order->invoice_number ?: $order->order_number }}</strong>
                        <span class="text-muted">· {{ $order->customer?->display_name ?? 'Walk-in' }}</span>
                        <span class="badge bg-secondary ms-1">{{ ucfirst($order->stockChannel()) }}</span>
                        <span class="badge bg-light text-dark border ms-1">{{ $order->status }}</span>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">Open order</a>
                </div>
                <div class="card-body">
                    @include('erp.sale-returns._form', [
                        'order' => $order,
                        'formAction' => route('sale-returns.store'),
                        'hiddenFields' => ['order_id' => $order->id],
                    ])
                </div>
                <div class="card-footer text-end">
                    <button type="submit" form="sale-return-form-{{ $order->id }}" class="btn btn-warning">
                        Submit Sale Return
                    </button>
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
        if (!customerId) {
            statusEl.textContent = 'Select a customer.';
            return;
        }
        if (q.length < 2) {
            statusEl.textContent = 'Type at least 2 characters.';
            return;
        }
        statusEl.textContent = 'Searching…';
        fetch(lookupUrl + '?customer_id=' + encodeURIComponent(customerId) + '&q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const rows = data.matches || [];
                if (!rows.length) {
                    statusEl.textContent = 'No returnable invoice found for that design.';
                    return;
                }
                statusEl.textContent = rows.length + ' match(es). Latest first.';
                rows.forEach(function (row) {
                    const tr = document.createElement('tr');
                    const inv = row.invoice_number || row.order_number || ('#' + row.order_id);
                    const label = (row.product || '') + (row.variation ? ' (' + row.variation + ')' : '') + (row.sku ? ' · SKU ' + row.sku : '');
                    tr.innerHTML =
                        '<td class="fw-semibold">' + inv + '<div class="small text-muted">' + (row.status || '') + '</div></td>' +
                        '<td>' + (row.order_date || '—') + '</td>' +
                        '<td>' + label + '</td>' +
                        '<td class="text-end">' + money(row.returnable_qty) + '</td>' +
                        '<td class="text-end text-muted">' + money(row.list_unit_price) + '</td>' +
                        '<td class="text-end fw-semibold">' + money(row.effective_unit_price) + '</td>' +
                        '<td class="text-end"><a class="btn btn-sm btn-primary" href="' + createUrl + '?order_id=' + row.order_id + '">Open return</a></td>';
                    body.appendChild(tr);
                });
                wrap.classList.remove('d-none');
            })
            .catch(function () {
                statusEl.textContent = 'Lookup failed. Try again.';
            });
    });
})();
</script>
@endpush

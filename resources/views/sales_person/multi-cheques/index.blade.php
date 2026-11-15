@extends('sales_person.layout')

@section('title', 'Multi Cheque')

@section('content')
<div class="container-fluid py-3">
    <h1 class="h4 mb-1">Multi Cheque</h1>
    <p class="text-muted small">එක cheque එකක් shops / orders කිහිපයකට බෙදන්න. එක order එකකට payment දාන පරණ Add Payment එක ඒ විදිහටම තියෙනවා.</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-semibold">අලුත් cheque එක</div>
        <div class="card-body">
            <form method="POST" action="{{ route('sales-person.multi-cheques.store') }}" enctype="multipart/form-data" id="multi-cheque-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cheque amount (Rs)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="cheque_amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cheque date</label>
                        <input type="date" name="cheque_date" class="form-control" value="{{ old('cheque_date') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cheque number</label>
                        <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number') }}" maxlength="100" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bank <span class="text-muted">(optional)</span></label>
                        <input type="text" name="cheque_bank" class="form-control" value="{{ old('cheque_bank') }}" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cheque photo <span class="text-muted">(optional)</span></label>
                        <input type="file" name="cheque_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Note <span class="text-muted">(optional)</span></label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="500">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cheque_exception_requested" value="1" id="cheque_exception_requested" @checked(old('cheque_exception_requested'))>
                            <label class="form-check-label small" for="cheque_exception_requested">Cheque date is outside the normal 45-day window</label>
                        </div>
                        <textarea name="cheque_exception_reason" class="form-control form-control-sm mt-2 {{ old('cheque_exception_requested') ? '' : 'd-none' }}" id="cheque_exception_reason" rows="2" maxlength="5000" placeholder="English explanation, at least 30 characters">{{ old('cheque_exception_reason') }}</textarea>
                    </div>
                </div>

                <hr>
                <label class="form-label">Order number search</label>
                <div class="input-group mb-2">
                    <input type="text" id="order-search" class="form-control" placeholder="ORD140" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="order-search-btn">Search</button>
                </div>
                <div id="order-search-results" class="list-group mb-3"></div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-2">
                        <thead class="table-light">
                            <tr>
                                <th>Order</th>
                                <th>Business</th>
                                <th class="text-end">Due</th>
                                <th style="width:160px">This cheque pays</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cheque-lines"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Split total</th>
                                <th class="text-end" id="split-total">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="small text-muted mb-3" id="split-hint">Orders දෙකක්වත් add කරලා, amounts එකතුව cheque amount එකට සමාන කරන්න.</p>
                <button type="submit" class="btn btn-primary">Save cheque</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">දාපු multi cheques</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Multi pay no</th>
                        <th>Cheque amount</th>
                        <th>Date</th>
                        <th>Cheque no</th>
                        <th>Orders</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td class="fw-semibold">{{ $batch->batch_number }}</td>
                            <td class="font-monospace">{{ number_format((float) $batch->amount, 2) }}</td>
                            <td>{{ $batch->cheque_date?->format('Y-m-d') }}</td>
                            <td>{{ $batch->cheque_number }}</td>
                            <td class="small">
                                @foreach($batch->payments as $payment)
                                    <div>
                                        {{ $payment->order?->order_number ?? '—' }}
                                        — Rs {{ number_format((float) $payment->amount, 2) }}
                                        <span class="text-muted">{{ $payment->order?->customer?->display_name }}</span>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">තව multi cheque එකක් නැහැ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white">{{ $batches->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var searchUrl = @json(route('sales-person.multi-cheques.orders'));
    var linesBody = document.getElementById('cheque-lines');
    var results = document.getElementById('order-search-results');
    var searchInput = document.getElementById('order-search');
    var chequeAmount = document.getElementById('cheque_amount');
    var splitTotal = document.getElementById('split-total');
    var hint = document.getElementById('split-hint');
    var added = {};

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function money(n) {
        var v = parseFloat(n);
        return (isNaN(v) ? 0 : v).toFixed(2);
    }

    function refreshTotal() {
        var sum = 0;
        var count = 0;
        linesBody.querySelectorAll('.line-amount').forEach(function (input) {
            sum += parseFloat(input.value || '0') || 0;
            count++;
        });
        splitTotal.textContent = sum.toFixed(2);
        var cheque = parseFloat(chequeAmount.value || '0') || 0;
        var ok = count >= 2 && Math.abs(sum - cheque) < 0.02 && cheque > 0;
        hint.textContent = ok
            ? 'Cheque amount එකට match වෙනවා.'
            : 'Orders දෙකක්වත් add කරලා, amounts එකතුව cheque amount එකට සමාන කරන්න. දැන් Rs ' + sum.toFixed(2) + ' / ' + cheque.toFixed(2);
        hint.className = 'small mb-3 ' + (ok ? 'text-success' : 'text-muted');
    }

    function addLine(item) {
        if (added[item.id]) return;
        added[item.id] = true;
        var index = linesBody.children.length;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="fw-semibold">' + esc(item.order_number) +
                '<input type="hidden" name="lines[' + index + '][order_id]" value="' + item.id + '"></td>' +
            '<td>' + esc(item.business) + '</td>' +
            '<td class="text-end">' + money(item.due) + '</td>' +
            '<td><input type="number" step="0.01" min="0.01" max="' + money(item.due) + '" name="lines[' + index + '][amount]" class="form-control form-control-sm line-amount" value="' + money(item.due) + '" required></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger line-remove">Remove</button></td>';
        tr.querySelector('.line-amount').addEventListener('input', refreshTotal);
        tr.querySelector('.line-remove').addEventListener('click', function () {
            delete added[item.id];
            tr.remove();
            reindex();
            refreshTotal();
        });
        linesBody.appendChild(tr);
        results.innerHTML = '';
        searchInput.value = '';
        refreshTotal();
    }

    function reindex() {
        Array.prototype.forEach.call(linesBody.children, function (tr, i) {
            tr.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/lines\[\d+\]/, 'lines[' + i + ']');
            });
        });
    }

    function search() {
        var q = searchInput.value.trim();
        results.innerHTML = '';
        if (q.length < 2) return;
        fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                if (!rows.length) {
                    results.innerHTML = '<div class="list-group-item small text-muted">Order එකක් හම්බුනේ නැහැ, නැත්නම් due නැහැ.</div>';
                    return;
                }
                rows.forEach(function (item) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action';
                    btn.textContent = item.order_number + ' · ' + item.business + ' · due Rs ' + money(item.due);
                    btn.addEventListener('click', function () { addLine(item); });
                    results.appendChild(btn);
                });
            });
    }

    document.getElementById('order-search-btn').addEventListener('click', search);
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); search(); }
    });
    chequeAmount.addEventListener('input', refreshTotal);
    document.getElementById('cheque_exception_requested').addEventListener('change', function () {
        document.getElementById('cheque_exception_reason').classList.toggle('d-none', !this.checked);
    });
    document.getElementById('multi-cheque-form').addEventListener('submit', function (e) {
        if (linesBody.children.length < 2) {
            e.preventDefault();
            alert('Orders දෙකක්වත් add කරන්න. එක order එකක් නම් ඒ order එකේ Add Payment එක පාවිච්චි කරන්න.');
        }
    });
})();
</script>
@endsection

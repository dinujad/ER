@extends('accounting::phase2._page')
@section('page_actions')
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/phase5/ocr/'.$document->id.'/download') }}"><i class="bi bi-file-earmark"></i> Original file</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/phase5/ocr') }}">All documents</a>
@endsection
@section('page_body')
@php
    $conf = $fields['field_confidence'] ?? [];
    $badge = function ($key) use ($conf) {
        $c = (int) ($conf[$key] ?? 0);
        if ($c >= 85) return '<span class="badge bg-success-subtle text-success border">sure</span>';
        if ($c >= 50) return '<span class="badge bg-warning-subtle text-warning-emphasis border">check</span>';
        return '<span class="badge bg-light text-muted border">not found</span>';
    };
    $errors_ = $extraction ? (json_decode($extraction->validation_errors ?? '[]', true) ?: []) : [];
    $raw = $extraction ? (json_decode($extraction->raw_text ?? '{}', true) ?: []) : [];
    $suppliers = \Illuminate\Support\Facades\DB::table('suppliers')->orderBy('name')->get(['id', 'name', 'company_name', 'tax_vat_no']);
@endphp
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<div class="d-flex flex-wrap gap-3 small mb-3">
    <span>Status: <strong>{{ str_replace('_', ' ', $document->status) }}</strong></span>
    @if($extraction)
        <span>Read with: <strong>{{ $raw['method'] ?? $extraction->extractor }}</strong></span>
        <span>Confidence: <strong>{{ (int) $extraction->confidence }}%</strong></span>
        <span>Amounts add up: <strong class="{{ $extraction->amounts_valid ? 'text-success' : 'text-danger' }}">{{ $extraction->amounts_valid ? 'yes' : 'no' }}</strong></span>
        @if($extraction->duplicate_suspected)<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Possible duplicate: {{ $extraction->duplicate_reason }}</span>@endif
    @endif
    @if($document->draft_source_type === 'purchase' && $document->draft_source_id)
        <a href="{{ url('accounting/phase6/bills/' . $document->draft_source_id) }}" class="btn btn-success btn-xs"><i class="bi bi-box-arrow-up-right"></i> Open draft bill</a>
        @if(\Illuminate\Support\Facades\Route::has('purchases.edit'))<a href="{{ route('purchases.edit', $document->draft_source_id) }}" class="btn btn-outline-primary btn-xs"><i class="bi bi-pencil"></i> Add items &amp; confirm</a>@endif
    @endif
</div>

<div class="row g-3">
<div class="col-lg-7">
@if($extraction)
    @if($errors_)<div class="alert alert-warning small py-2">@foreach($errors_ as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <div class="card"><div class="card-header"><strong>Review the fields</strong> <span class="small text-muted">— correct anything marked "check" or "not found"</span></div><div class="card-body">
    <form method="post" action="{{ url('accounting/phase5/ocr/'.$document->id.'/draft') }}">@csrf
        <input type="hidden" name="fields[currency]" value="LKR">
        <input type="hidden" name="fields[create_purchase_draft]" value="1">
        <div class="mb-2">
            <label class="form-label small mb-0">Supplier {!! $badge('supplier') !!} @if(!empty($fields['supplier_vat_no']))<span class="text-muted">VAT no. on invoice: {{ $fields['supplier_vat_no'] }}</span>@endif</label>
            <select name="fields[supplier_id]" class="form-select form-select-sm" required>
                <option value="">— choose supplier —@if(!empty($fields['supplier_name']) && empty($fields['supplier_id'])) (read as "{{ $fields['supplier_name'] }}")@endif</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(($fields['supplier_id'] ?? null) == $s->id)>{{ $s->company_name ?: $s->name }}{{ $s->tax_vat_no ? ' · ' . $s->tax_vat_no : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label small mb-0">Supplier invoice no. {!! $badge('invoice_number') !!}</label><input class="form-control form-control-sm" name="fields[invoice_number]" value="{{ $fields['invoice_number'] ?? '' }}" required></div>
            <div class="col-md-3"><label class="form-label small mb-0">Invoice date {!! $badge('invoice_date') !!}</label><input type="date" class="form-control form-control-sm" name="fields[invoice_date]" value="{{ $fields['invoice_date'] ?? '' }}"></div>
            <div class="col-md-3"><label class="form-label small mb-0">Due date</label><input type="date" class="form-control form-control-sm" name="fields[due_date]" value="{{ $fields['due_date'] ?? '' }}"></div>
            <div class="col-md-4"><label class="form-label small mb-0">Amount excl. VAT {!! $badge('subtotal') !!}</label><input type="number" step="0.01" class="form-control form-control-sm ocr-amt" name="fields[subtotal]" value="{{ $fields['subtotal'] ?? '' }}"></div>
            <div class="col-md-4"><label class="form-label small mb-0">VAT {!! $badge('tax_amount') !!}</label><input type="number" step="0.01" class="form-control form-control-sm ocr-amt" name="fields[tax_amount]" value="{{ $fields['tax_amount'] ?? '' }}"></div>
            <div class="col-md-4"><label class="form-label small mb-0">Total {!! $badge('total') !!}</label><input type="number" step="0.01" class="form-control form-control-sm ocr-amt" name="fields[total]" value="{{ $fields['total'] ?? '' }}" required></div>
        </div>
        <div class="small mt-2" id="ocrCheck"></div>
        <div class="mt-3 d-flex justify-content-between align-items-center">
            <div class="small text-muted">Creates a <strong>draft bill</strong> (nothing is posted). Add the items in Purchases, then confirm.</div>
            <button class="btn btn-primary btn-sm" @disabled($document->draft_source_type === 'purchase')>Create draft bill</button>
        </div>
    </form>
    </div></div>
    <script>
    (function () {
        var f = document.querySelectorAll('.ocr-amt');
        function chk() {
            var s = parseFloat(f[0].value) || 0, t = parseFloat(f[1].value) || 0, tot = parseFloat(f[2].value) || 0, el = document.getElementById('ocrCheck');
            if (!tot) { el.textContent = ''; return; }
            var d = Math.round((s + t - tot) * 100) / 100;
            el.innerHTML = d === 0 ? '<span class="text-success">✓ Amount + VAT = Total</span>' : '<span class="text-danger">Amount + VAT differs from Total by ' + d.toFixed(2) + '</span>';
        }
        f.forEach(function (x) { x.addEventListener('input', chk); }); chk();
    })();
    </script>
@else
    <p class="text-muted">Not read yet.</p>
@endif
</div>

<div class="col-lg-5">
    <div class="card mb-3"><div class="card-body">
        <form method="post" action="{{ url('accounting/phase5/ocr/'.$document->id.'/process') }}">@csrf
            <button class="btn btn-sm btn-secondary"><i class="bi bi-arrow-repeat"></i> Read the file again</button>
        </form>
        <hr>
        <form method="post" action="{{ url('accounting/phase5/ocr/'.$document->id.'/process') }}">@csrf
            <label class="form-label small mb-1">Scanned copy the server can't read? Paste or type the invoice text here:</label>
            <textarea name="pasted_text" class="form-control form-control-sm" rows="6" placeholder="Tax Invoice No: INV-1042&#10;Date: 05/11/2026&#10;Supplier's TIN: 114455667&#10;VAT 18% 3,600.00&#10;Total 23,600.00"></textarea>
            <button class="btn btn-sm btn-outline-primary mt-2">Read this text</button>
        </form>
    </div></div>
    @if(!empty($raw['text']))
        <div class="card"><div class="card-header small"><strong>Text read from the document</strong></div>
            <div class="card-body"><pre class="small mb-0" style="max-height:360px;overflow:auto;white-space:pre-wrap">{{ $raw['text'] }}</pre></div></div>
    @endif
</div>
</div>
@endsection

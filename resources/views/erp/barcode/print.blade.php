@extends('erp.layout')

@section('title', 'Print Labels')

@section('css')
<style>
    @media print {
        .sidebar, .erp-sidebar, .erp-mobile-header, .navbar, nav, header, footer,
        #print-station, .no-print, .alert { display: none !important; }
        .erp-wrapper { display: block !important; }
        .content { padding: 0 !important; max-width: none !important; margin: 0 !important; }
        body { background: white !important; }
        #label-sheet { display: block !important; }
        .sticker {
            width: 1.5in;
            height: 0.98in;
            page-break-after: always;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            margin: 0;
            border: none;
            background: white;
        }
        .sticker svg {
            max-width: 94% !important;
            width: auto !important;
            height: auto !important;
            max-height: 0.58in;
        }
        @page { size: 1.5in 0.98in; margin: 0; }
    }
    .sticker {
        width: 1.5in;
        height: 0.98in;
        background: white;
        margin: 8px;
        display: inline-flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border: 1px dotted #9ca3af;
        overflow: hidden;
    }
    .sticker svg {
        max-width: 94% !important;
        width: auto !important;
        height: auto !important;
        max-height: 0.58in;
    }
    .sticker .s-name, .sticker .s-var {
        font-size: 7.5px;
        font-weight: bold;
        text-transform: uppercase;
        line-height: 1;
        max-width: 95%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sticker .s-price { font-size: 9px; font-weight: bold; margin-top: 1px; }
    .qty-input { width: 88px; }
</style>
@endsection

@section('content')
<div id="print-station" class="no-print">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-upc-scan me-2"></i>Print Labels</h1>
            <p class="text-muted small mb-0">
                Search the product you made. Every colour / size row appears. Type print qty, then print stickers.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="use-made-qty">Use made qty</button>
            <button type="button" class="btn btn-primary" id="print-labels-btn">
                <i class="bi bi-printer me-1"></i> Print labels
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">Search product</label>
            <input type="text" id="search_product" class="form-control form-control-lg" placeholder="Product name / SKU / colour / size…" autofocus>
            <div class="form-text">Type 2+ letters. Matching product and all colour × size variations open with a qty box.</div>
        </div>
    </div>

    <div id="empty-search" class="text-muted mb-3">Search the product — colour and size rows will show here.</div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product / Variation</th>
                        <th>Colour</th>
                        <th>Size</th>
                        <th>Barcode</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Made</th>
                        <th style="width:110px">Print qty</th>
                    </tr>
                </thead>
                @foreach($products as $p)
                    @php
                        $hasVars = $p->variations->isNotEmpty();
                        $productSearch = strtolower(trim($p->name . ' ' . $p->sku . ' ' . $p->barcode));
                    @endphp
                    <tbody class="product-group d-none" data-product-id="{{ $p->id }}" data-product-search="{{ $productSearch }}">
                        <tr class="product-row table-light">
                            <td colspan="6">
                                <strong>{{ $p->name }}</strong>
                                <span class="badge bg-secondary">{{ $p->product_type }}</span>
                                @if(($p->status ?? '') === 'draft')
                                    <span class="badge bg-warning text-dark">Draft / new make</span>
                                @endif
                                @if($hasVars)
                                    <span class="badge bg-info text-dark">{{ $p->variations->count() }} variations</span>
                                @endif
                            </td>
                            <td>
                                @unless($hasVars)
                                    <input type="number" min="0" max="9999" class="form-control form-control-sm qty-input"
                                           data-name="{{ $p->name }}"
                                           data-variation=""
                                           data-sku="{{ \App\Support\ProductLabel::productBarcode($p) }}"
                                           data-price="LKR {{ number_format((float) $p->selling_price, 2) }}"
                                           value="0">
                                @endunless
                            </td>
                        </tr>
                        @foreach($p->variations as $variation)
                            @php
                                $color = \App\Support\ProductLabel::attributeValue($variation, 'Color', 'Colour');
                                $size = \App\Support\ProductLabel::attributeValue($variation, 'Size');
                                $made = (float) ($packedQty[$variation->id] ?? 0);
                                $madeQty = $made > 0 ? (int) round($made) : 0;
                                $varSku = \App\Support\ProductLabel::variationBarcode($variation);
                                $varPrice = \App\Support\ProductLabel::sellPrice($variation);
                                $varLabel = \App\Support\ProductLabel::attributesLabel($variation);
                            @endphp
                            <tr class="var-row" data-product-id="{{ $p->id }}" data-var-search="{{ \App\Support\ProductLabel::searchHaystack($variation) }}">
                                <td class="ps-4">{{ $variation->variation_name }}</td>
                                <td>{{ $color ?: '—' }}</td>
                                <td>{{ $size ?: '—' }}</td>
                                <td class="font-monospace">{{ $varSku }}</td>
                                <td class="text-end">{{ number_format($varPrice, 2) }}</td>
                                <td class="text-end text-muted">{{ $made > 0 ? number_format($made, 0) : '—' }}</td>
                                <td>
                                    <input type="number" min="0" max="9999" class="form-control form-control-sm qty-input"
                                           data-made="{{ $madeQty }}"
                                           data-name="{{ $p->name }}"
                                           data-variation="{{ $varLabel }}"
                                           data-sku="{{ $varSku }}"
                                           data-price="LKR {{ number_format($varPrice, 2) }}"
                                           value="0">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>
    </div>
</div>

<div id="label-sheet"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
<script>
window.renderC128 = function (target, code, stickerWidthIn) {
    code = String(code || '').trim();
    if (!code) return false;
    var inch = stickerWidthIn > 0 ? stickerWidthIn : 1.5;
    var usablePx = inch * 96 * 0.90;
    var modules = (code.length * 11) + 35;
    var barW = usablePx / modules;
    if (barW > 2.0) barW = 2.0;
    if (barW < 1.05) barW = 1.05;
    var quiet = Math.max(4, Math.min(6, Math.round(barW * 4)));
    var barH = Math.max(26, Math.min(38, inch * 96 * 0.36));
    try {
        JsBarcode(target, code, {
            format: 'CODE128',
            width: barW,
            height: barH,
            displayValue: true,
            fontSize: 9,
            margin: quiet,
            marginTop: 0,
            marginBottom: 0,
            textMargin: 1,
            lineColor: '#000000',
            background: '#ffffff'
        });
        return true;
    } catch (e) {
        return false;
    }
};
</script>
<script>
(function () {
    const search = document.getElementById('search_product');
    const emptyHint = document.getElementById('empty-search');

    function applySearch() {
        const term = (search.value || '').toLowerCase().trim();
        let shown = 0;
        document.querySelectorAll('.product-group').forEach(function (group) {
            if (term.length < 2) {
                group.classList.add('d-none');
                return;
            }
            const productHit = (group.dataset.productSearch || '').indexOf(term) !== -1;
            const varRows = group.querySelectorAll('.var-row');
            let varHit = false;
            varRows.forEach(function (row) {
                const thisVarHit = (row.dataset.varSearch || '').indexOf(term) !== -1;
                if (thisVarHit) varHit = true;
                row.style.display = (productHit || thisVarHit) ? '' : 'none';
            });
            const show = productHit || varHit;
            group.classList.toggle('d-none', !show);
            if (show) shown++;
        });
        if (emptyHint) {
            emptyHint.style.display = (term.length < 2 || shown === 0) ? '' : 'none';
            emptyHint.textContent = (term.length >= 2 && shown === 0)
                ? 'No matching product / variation.'
                : 'Search the product — colour and size rows will show here.';
        }
    }

    search.addEventListener('input', applySearch);

    document.getElementById('use-made-qty')?.addEventListener('click', function () {
        document.querySelectorAll('.product-group:not(.d-none) .qty-input').forEach(function (input) {
            const made = parseInt(input.getAttribute('data-made') || '0', 10);
            if (made > 0) input.value = made;
        });
    });

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.getElementById('print-labels-btn').addEventListener('click', function () {
        const sheet = document.getElementById('label-sheet');
        sheet.innerHTML = '';
        let n = 0;
        document.querySelectorAll('.product-group:not(.d-none) .qty-input').forEach(function (input) {
            const qty = parseInt(input.value || '0', 10);
            if (!qty || qty < 1) return;
            const name = input.getAttribute('data-name') || '';
            const variation = input.getAttribute('data-variation') || '';
            const sku = input.getAttribute('data-sku') || '';
            const price = input.getAttribute('data-price') || '';
            for (let i = 0; i < qty; i++) {
                n++;
                const id = 'lbl-' + n;
                sheet.insertAdjacentHTML('beforeend',
                    '<div class="sticker">' +
                        '<div class="s-name">' + escapeHtml(name) + '</div>' +
                        (variation ? '<div class="s-var">' + escapeHtml(variation) + '</div>' : '') +
                        '<svg id="' + id + '"></svg>' +
                        '<div class="s-price">' + escapeHtml(price) + '</div>' +
                    '</div>'
                );
                if (!window.renderC128('#' + id, sku, 1.5)) {
                    const el = document.getElementById(id);
                    if (el) el.outerHTML = '<div class="s-var">' + escapeHtml(sku) + '</div>';
                }
            }
        });
        if (n === 0) {
            alert('Enter print qty on at least one colour / size row.');
            return;
        }
        window.print();
    });
})();
</script>
@endpush

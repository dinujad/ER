<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Labels — {{ $product->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    @includeIf('erp.barcode._c128.js')
    <script>
    if (typeof window.renderC128 !== 'function') {
        window.renderC128 = function (target, code) {
            code = String(code || '').trim();
            if (!code) return false;
            try {
                JsBarcode(target, code, { format: 'CODE128', width: 1.4, height: 32, displayValue: true, fontSize: 9, margin: 4, lineColor: '#000', background: '#fff' });
                return true;
            } catch (e) { return false; }
        };
    }
    </script>
    <style>
        :root {
            --sticker-width: 1.5in;
            --sticker-height: 0.98in;
            --paper-width: 1.5in;
            --top-margin: 0.05in;
        }

        @media print {
            @page {
                size: var(--paper-width) var(--sticker-height);
                margin: 0;
            }
            body {
                margin: 0;
                padding: var(--top-margin) 0 0 0;
            }
            #controls { display: none; }
            .sticker {
                width: var(--sticker-width);
                height: var(--sticker-height);
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
        }

        body {
            font-family: Arial, sans-serif;
            background: #e5e7eb;
        }

        #controls {
            padding: 16px 20px;
            background: white;
            margin-bottom: 16px;
        }
        #controls h3 { margin: 0 0 12px; font-size: 15px; color: #1f2937; }
        #controls button, #controls .btn-link {
            padding: 8px 16px;
            font-size: 13px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        #controls .btn-ghost {
            background: #e5e7eb;
            color: #111827;
        }
        #controls table {
            width: 100%;
            max-width: 820px;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 13px;
        }
        #controls th, #controls td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
        }
        #controls th { color: #6b7280; font-weight: 600; font-size: 12px; }
        #controls .qty-input { width: 72px; padding: 4px 6px; }
        .hint { color: #6b7280; font-size: 12px; margin: 0 0 10px; }

        .sticker {
            width: var(--sticker-width);
            height: var(--sticker-height);
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

        .s-name {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1;
            margin-bottom: 1px;
            max-width: 95%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .s-var {
            font-size: 7px;
            color: #111;
            line-height: 1;
            margin-bottom: 1px;
            max-width: 95%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .s-price {
            font-size: 9px;
            font-weight: bold;
            line-height: 1;
            margin-top: 1px;
        }
    </style>
</head>
<body>

@php
    $labels = [];
    if ($product->variations->isNotEmpty()) {
        foreach ($product->variations as $variation) {
            $labels[] = [
                'id' => 'v' . $variation->id,
                'variation_id' => $variation->id,
                'name' => $product->name,
                'variation' => $variation->attributesLabel(),
                'color' => $variation->attributeValue('Color', 'Colour'),
                'size' => $variation->attributeValue('Size'),
                'sku' => $variation->barcodeValue(),
                'price' => 'LKR ' . number_format($variation->labelSellPrice(), 2),
            ];
        }
    } else {
        $labels[] = [
            'id' => 'p' . $product->id,
            'variation_id' => 0,
            'name' => $product->name,
            'variation' => '',
            'sku' => $product->barcodeValue(),
            'price' => $product->formatted_price,
        ];
    }
@endphp

<div id="controls">
    <h3>{{ $product->name }} — barcode labels</h3>
    @if($product->variations->isNotEmpty())
        <p class="hint">Each variation has its own barcode. Set qty for the sizes / colours you want, then print.</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Variation</th>
                <th>Barcode / SKU</th>
                <th>Price</th>
                <th>Qty</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($labels as $label)
                @php
                    $defaultQty = 10;
                    if ($product->variations->isNotEmpty() && $focusVariationId) {
                        $defaultQty = ((int) $label['variation_id'] === $focusVariationId) ? 40 : 0;
                    }
                @endphp
                <tr>
                    <td>
                        {{ $label['variation'] !== '' ? $label['variation'] : 'Default' }}
                        @if(!empty($label['color']) || !empty($label['size']))
                            <div style="color:#6b7280;font-size:12px;font-weight:400">
                                @if(!empty($label['color'])) Colour: {{ $label['color'] }} @endif
                                @if(!empty($label['size'])) Size: {{ $label['size'] }} @endif
                            </div>
                        @endif
                    </td>
                    <td style="font-family:monospace">{{ $label['sku'] }}</td>
                    <td>{{ $label['price'] }}</td>
                    <td>
                        <input type="number" min="0" max="500" class="qty-input" id="qty-{{ $label['id'] }}" value="{{ $defaultQty }}">
                    </td>
                    <td>
                        @if($product->variations->isNotEmpty())
                            <button type="button" class="btn-ghost" data-only="{{ $label['id'] }}">This only</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        @if($product->variations->isNotEmpty())
            <button type="button" class="btn-ghost" id="qty-all-10">All = 10</button>
            <button type="button" class="btn-ghost" id="qty-clear">Clear</button>
        @endif
        <button type="button" onclick="window.print()">🖨 Print Labels</button>
    </div>
</div>

<div id="stickers"></div>

<script>
    const labels = @json($labels);

    function qtyOf(id) {
        const el = document.getElementById('qty-' + id);
        const n = parseInt(el && el.value ? el.value : '0', 10);
        return isNaN(n) || n < 0 ? 0 : n;
    }

    function renderStickers() {
        const wrap = document.getElementById('stickers');
        wrap.innerHTML = '';
        labels.forEach(function (label) {
            const qty = qtyOf(label.id);
            for (let i = 0; i < qty; i++) {
                const svgId = 'barcode-' + label.id + '-' + i;
                const varHtml = label.variation
                    ? '<div class="s-var">' + escapeHtml(label.variation) + '</div>'
                    : '';
                wrap.insertAdjacentHTML('beforeend',
                    '<div class="sticker">' +
                        '<div class="s-name">' + escapeHtml(label.name) + '</div>' +
                        varHtml +
                        '<svg id="' + svgId + '"></svg>' +
                        '<div class="s-price">' + escapeHtml(label.price) + '</div>' +
                    '</div>'
                );
                if (!window.renderC128('#' + svgId, label.sku, 1.5)) {
                    document.getElementById(svgId).outerHTML = '<div class="s-var">' + escapeHtml(label.sku) + '</div>';
                }
            }
        });
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.querySelectorAll('.qty-input').forEach(function (input) {
        input.addEventListener('input', renderStickers);
        input.addEventListener('change', renderStickers);
    });

    document.querySelectorAll('[data-only]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const keep = btn.getAttribute('data-only');
            labels.forEach(function (label) {
                const el = document.getElementById('qty-' + label.id);
                if (!el) return;
                el.value = label.id === keep ? (parseInt(el.value, 10) > 0 ? el.value : 10) : 0;
            });
            renderStickers();
        });
    });

    const all10 = document.getElementById('qty-all-10');
    if (all10) {
        all10.addEventListener('click', function () {
            labels.forEach(function (label) {
                const el = document.getElementById('qty-' + label.id);
                if (el) el.value = 10;
            });
            renderStickers();
        });
    }

    const clearBtn = document.getElementById('qty-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            labels.forEach(function (label) {
                const el = document.getElementById('qty-' + label.id);
                if (el) el.value = 0;
            });
            renderStickers();
        });
    }

    document.addEventListener('DOMContentLoaded', renderStickers);
</script>

</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Print Labels</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    @php
        $paperW = (float) ($setting->paper_width ?? 1.5) ?: 1.5;
        $stickerW = (float) ($setting->sticker_width ?? 1.5) ?: 1.5;
        $stickerH = (float) ($setting->sticker_height ?? 0.98) ?: 0.98;
        $topM = (float) ($setting->top_margin ?? 0);
        $leftM = (float) ($setting->left_margin ?? 0);
        $rowD = (float) ($setting->row_distance ?? 0);
        $colD = (float) ($setting->col_distance ?? 0);
        $continuous = (bool) ($setting->is_continuous ?? true);
        $perRow = method_exists($setting, 'stickersPerRow') ? $setting->stickersPerRow() : max(1, (int) ($setting->stickers_per_row ?? 1));
    @endphp
    <style>
        @page {
            size: {{ $paperW }}in {{ $continuous ? 'auto' : '11in' }};
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }
        .label-container {
            width: {{ $paperW }}in;
        }
        .sticker {
            float: left;
            width: {{ $stickerW }}in;
            height: {{ $stickerH }}in;
            margin-left: {{ $leftM }}in;
            margin-top: {{ $topM }}in;
            margin-right: {{ $colD }}in;
            margin-bottom: {{ $rowD }}in;
            /* border: 1px dashed #ccc; Debug border */
            overflow: hidden;
            box-sizing: border-box;
            text-align: center;
            page-break-inside: avoid;
            background: white;
            
            /* Center Content Flexibly */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .barcode-box {
            width: 94%;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .barcode-box svg {
            max-width: 100% !important;
            width: auto !important;
            height: auto !important;
            max-height: 0.58in;
        }
        .text-line {
            font-size: 8px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 96%;
            line-height: 1.1;
        }
        .price {
            font-weight: bold;
            font-size: 8px;
            line-height: 1;
        }
        
        /* Clearfix for rows */
        .row-break {
            clear: both;
            height: 0;
        }
    </style>
</head>
<body>
    <div class="label-container">
        @php $count = 0; @endphp
        @foreach($items as $item)
            <div class="sticker">
                <div class="text-line truncate">{{ \Illuminate\Support\Str::limit($item['name'] ?? '', 28) }}</div>
                <div class="barcode-box">
                    <svg class="label-barcode" data-code="{{ e($item['barcode'] ?? $item['sku'] ?? '') }}"></svg>
                </div>
                @if(!empty($item['price']))
                    <div class="price">Rs. {{ $item['price'] }}</div>
                @endif
            </div>

            @php $count++; @endphp
            @if($count % $perRow === 0)
                <div class="row-break"></div>
            @endif
        @endforeach
    </div>
    @includeIf('erp.barcode._c128.js')
    <script>
    if (typeof window.renderC128 !== 'function') {
        window.renderC128 = function (target, code, stickerWidthIn) {
            code = String(code || '').trim();
            if (!code) return false;
            try {
                JsBarcode(target, code, { format: 'CODE128', width: 1.4, height: 32, displayValue: true, fontSize: 9, margin: 4, lineColor: '#000', background: '#fff' });
                return true;
            } catch (e) { return false; }
        };
    }
    </script>
    <script>
        var stickerIn = {{ json_encode($stickerW) }};
        document.querySelectorAll('.label-barcode').forEach(function (svg) {
            var code = svg.getAttribute('data-code') || '';
            if (!window.renderC128(svg, code, stickerIn)) {
                svg.outerHTML = '<div style="font-family:monospace;font-size:9px;">' + code + '</div>';
            }
        });
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>

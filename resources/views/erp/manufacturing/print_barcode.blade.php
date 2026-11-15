<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Barcodes</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --sticker-width: 1.5in;
            --sticker-height: 0.98in;
            --paper-width: 1.5in;
            --top-margin: 0.05in;
            --left-margin: 0in;
            --row-gap: 0in;
            --col-gap: 0in;
        }
        @media print {
            @page {
                size: var(--paper-width) var(--sticker-height);
                margin: 0;
            }
            body {
                margin: 0;
                padding: var(--top-margin) 0 0 var(--left-margin);
            }
            .sticker {
                width: var(--sticker-width);
                height: var(--sticker-height);
                page-break-after: always;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                overflow: hidden;
                margin: var(--row-gap) var(--col-gap) 0 0;
            }
        }
        body {
            font-family: sans-serif;
            background: #eee; /* Light gray for screen view */
        }
        .sticker {
            width: var(--sticker-width);
            height: var(--sticker-height);
            background: white;
            margin: 10px; /* Spacing for screen view */
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 1px solid #ddd; /* Border for screen view */
        }
        svg {
            width: 90% !important;
            height: 70% !important;
        }
        .text {
            font-size: 8px;
            font-weight: bold;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <div id="controls" style="padding: 20px; background: white; margin-bottom: 20px; text-align: center;">
        <h3>Batch: {{ ucfirst($batch->type) }} | Qty: {{ $batch->quantity }}</h3>
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: blue; color: white; border: none; cursor: pointer;">PRINT NOW</button>
    </div>

    @php
        $prefix = ($batch->type == 'good') ? 'PG' : 'PD';
        // Generate a base code: Prefix - JobNumber - BatchID
        // Example: PG-JOB001-52
        $code = $prefix . '-' . str_replace('JOB-', '', $batch->job->job_number ?? '000') . '-' . $batch->id;
    @endphp

    @for($i = 1; $i <= $batch->quantity; $i++)
        <div class="sticker">
            <svg id="barcode-{{$i}}"></svg>
            <!-- <div class="text">{{ $batch->job->style_ref ?? 'STYLE' }}</div> -->
        </div>
    @endfor

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @for($i = 1; $i <= $batch->quantity; $i++)
                JsBarcode("#barcode-{{$i}}", "{{ $code }}", {
                    format: "CODE128",
                    width: 1.5,
                    height: 36,
                    displayValue: true,
                    fontSize: 9,
                    margin: 0
                });
            @endfor
        });
    </script>

    <style>
        @media print {
            #controls { display: none; }
            .sticker { border: none; margin: 0; }
        }
    </style>

</body>
</html>

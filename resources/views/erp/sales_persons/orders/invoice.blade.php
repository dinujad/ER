<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TAX INVOICE - {{ $order->taxInvoiceSerialNumber() }}</title>
    <style>
        @page { margin: 10mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 0;
            line-height: 1.35;
        }
        .brand {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .brand td { vertical-align: middle; border: none; padding: 0; }
        .logo {
            width: 58px;
            height: auto;
        }
        .company-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: 0.3px;
        }
        .company-meta {
            font-size: 9px;
            color: #555;
            margin-top: 2px;
        }
        .doc-title {
            text-align: right;
            font-size: 20px;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .box {
            border: 1px solid #222;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .box td, .box th {
            border: 1px solid #222;
            padding: 5px 6px;
            vertical-align: top;
        }
        .label { font-weight: 700; }
        .muted { color: #444; }
        .grid-2 { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .grid-2 > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; border: none; }
        .grid-2 > tbody > tr > td:first-child { padding-right: 4px; }
        .grid-2 > tbody > tr > td:last-child { padding-left: 4px; }
        .parties {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        .parties > tbody > tr > td {
            width: 50%;
            border: 1px solid #222;
            vertical-align: top;
            padding: 0;
        }
        .party-inner {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .party-inner td {
            border: none;
            border-bottom: 1px solid #ddd;
            padding: 5px 7px;
            vertical-align: top;
            height: 22px;
        }
        .party-inner tr:last-child td { border-bottom: none; }
        .party-label {
            font-weight: 700;
            display: inline-block;
            min-width: 118px;
        }
        .section-gap { margin-top: 6px; }
        .items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items th, .items td {
            border: 1px solid #222;
            padding: 5px 6px;
            vertical-align: top;
        }
        .items th {
            background: #f3f5f8;
            font-weight: 700;
            text-align: left;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-label { font-weight: 700; }
        .words, .mode {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .words td, .mode td {
            border: 1px solid #222;
            padding: 6px;
        }
        .footer-note {
            margin-top: 8px;
            font-size: 8px;
            color: #666;
        }
        .ack-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .ack-box td {
            border: 1px solid #222;
            padding: 8px 10px;
            vertical-align: top;
        }
        .ack-text {
            font-size: 9.5px;
            line-height: 1.45;
            margin-bottom: 18px;
        }
        .sign-line {
            border-bottom: 1px solid #222;
            width: 55%;
            height: 28px;
            margin-top: 8px;
        }
        .sign-caption {
            font-size: 9px;
            margin-top: 4px;
            font-weight: 700;
        }
        .pay-sign-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .pay-sign-wrap td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .section-label {
            display: inline-block;
            background: #1e3a5f;
            color: #fff;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 0.4px;
            padding: 4px 10px;
            min-width: 86px;
            text-align: center;
        }
        .pay-fields {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .pay-fields td {
            border: none;
            padding: 4px 8px 6px 0;
            font-size: 10px;
            vertical-align: middle;
        }
        .pay-fields .label-cell {
            width: 92px;
            padding-right: 8px;
        }
        .dotted-line {
            border-bottom: 1px dotted #333;
            display: inline-block;
            min-width: 90px;
            height: 12px;
            vertical-align: bottom;
        }
        .pay-row-line {
            border-top: 1px solid #000;
            margin: 6px 0 10px;
        }
        .sign-fields {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .sign-fields td {
            border: none;
            padding: 4px 10px 0 0;
            text-align: center;
            vertical-align: bottom;
            width: 33.33%;
        }
        .sign-fields .label-cell {
            width: 92px;
            text-align: left;
            vertical-align: middle;
            padding-right: 8px;
        }
        .office-sign-line {
            border-bottom: 1px dotted #333;
            height: 26px;
            margin: 0 8px;
        }
        .office-sign-caption {
            font-size: 9px;
            margin-top: 3px;
        }
    </style>
</head>
<body>
@php
    $company = config('dermas.company', []);
    $vat = $order->taxInvoiceVatBreakdown();
    $invoiceDate = ($order->updated_at ?? $order->created_at ?? now())->format('m/d/Y');
    $supplyDate = ($order->order_date ?? $order->created_at ?? now());
    $supplyDateFmt = $supplyDate instanceof \Carbon\Carbon
        ? $supplyDate->format('m/d/Y')
        : \Carbon\Carbon::parse($supplyDate)->format('m/d/Y');
    $purchaserTin = $order->customer?->tin ?: null;
    $placeOfSupply = $company['address'] ?? 'Bandarawela';
    $groupedItems = collect($order->items)
        ->filter(fn ($item) => (float) $item->quantity > 0)
        ->groupBy(function ($item) {
            return ($item->product_id ?? 'p0') . '|' . number_format((float) $item->unit_price, 2, '.', '');
        })
        ->map(function ($group) {
            $first = $group->first();
            $name = $first->product?->name ?? 'Product';
            if ($first->variation?->variation_name) {
                $name .= ' (' . $first->variation->variation_name . ')';
            }
            $sku = $first->product?->sku;
            return [
                'reference' => $sku ?: '—',
                'description' => $name,
                'quantity' => (float) $group->sum('quantity'),
                'unit_price' => (float) $first->unit_price,
                'amount' => (float) $group->sum('total_price'),
            ];
        })
        ->values();
    $subtotal = (float) ($order->subtotal ?? $order->items->sum('total_price'));
    $discount = (float) ($order->discount_amount ?? 0);
    $transport = (float) ($order->shipping ?? 0);
    $otherExpenses = (float) ($order->invoice_other_expenses ?? 0);
    $salesPersonName = $order->salesPerson?->name ?: '—';
    $salesPersonPhone = filled($order->salesPerson?->phone) ? $order->salesPerson->phone : '—';
    $additional = trim(implode(' · ', array_filter([
        $order->order_number ? 'Order ' . $order->order_number : null,
        ($order->issued_bags_count ?? null) !== null ? 'Bags: ' . $order->issued_bags_count : null,
        ($order->issued_bundle_count ?? null) !== null ? 'Bundles: ' . $order->issued_bundle_count : null,
        $discount > 0 ? 'Discount Rs ' . number_format($discount, 2) : null,
        $transport > 0 ? 'Transport Rs ' . number_format($transport, 2) : null,
        $otherExpenses > 0 ? 'Other Rs ' . number_format($otherExpenses, 2) : null,
    ])));
@endphp

    <table class="brand">
        <tr>
            <td style="width: 64px;">
                <img src="{{ public_path('images/logo.png') }}" alt="DERMAS" class="logo">
            </td>
            <td>
                <div class="company-name">{{ $company['name'] ?? 'DERMAS APPAREL (PVT) LTD' }}</div>
                <div class="company-meta">
                    {{ $company['address'] ?? 'Vishaka Mawatha, Bandarawela' }}<br>
                    {{ $company['telephone'] ?? '' }}@if(!empty($company['email'])) | {{ $company['email'] }}@endif
                </div>
            </td>
            <td style="width: 34%;">
                <div class="doc-title">TAX INVOICE</div>
            </td>
        </tr>
    </table>

    <table class="grid-2">
        <tr>
            <td>
                <table class="box">
                    <tr>
                        <td style="width: 42%;"><span class="label">Date of Invoice</span></td>
                        <td>{{ $invoiceDate }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="box">
                    <tr>
                        <td style="width: 42%;"><span class="label">Tax Invoice No.</span></td>
                        <td><strong>{{ $order->taxInvoiceSerialNumber() }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <table class="party-inner">
                    <tr>
                        <td><span class="party-label">Supplier's TIN:</span> {{ !empty($company['tin']) ? $company['tin'] : '—' }}</td>
                    </tr>
                    <tr>
                        <td><span class="party-label">Supplier's Name:</span> {{ $company['name'] ?? 'DERMAS APPAREL (PVT) LTD' }}</td>
                    </tr>
                    <tr>
                        <td><span class="party-label">Address:</span> {{ $company['address'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td><span class="party-label">Telephone No.:*</span> {{ $company['telephone'] ?? '—' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="party-inner">
                    <tr>
                        <td><span class="party-label">Purchaser's TIN:</span> {{ $purchaserTin ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td><span class="party-label">Purchaser's Name:</span> {{ filled($order->customer?->business_name) ? $order->customer->business_name : ($order->customer?->display_name ?? '—') }}</td>
                    </tr>
                    <tr>
                        <td><span class="party-label">Address:</span> {{ $order->customer?->display_address ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td><span class="party-label">Telephone No.:*</span> {{ $order->customer?->phone ?: '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="grid-2 section-gap">
        <tr>
            <td>
                <table class="box">
                    <tr>
                        <td style="width: 42%;"><span class="label">Date of Supply</span></td>
                        <td>{{ $supplyDateFmt }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="box">
                    <tr>
                        <td style="width: 42%;"><span class="label">Place of Supply:*</span></td>
                        <td>{{ $placeOfSupply }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="grid-2 section-gap">
        <tr>
            <td>
                <table class="box">
                    <tr>
                        <td style="width: 42%;"><span class="label">Sales Person</span></td>
                        <td>{{ $salesPersonName }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="box">
                    <tr>
                        <td style="width: 42%;"><span class="label">Sales Person Contact</span></td>
                        <td>{{ $salesPersonPhone }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="box section-gap">
        <tr>
            <td>
                <span class="label">Additional Information if any:*</span>
                <div class="muted" style="margin-top:3px;">{{ $additional !== '' ? $additional : '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 12%;">Reference*</th>
                <th style="width: 38%;">Description of Goods or Services</th>
                <th style="width: 12%;" class="text-center">Quantity</th>
                <th style="width: 18%;" class="text-right">Unit Price</th>
                <th style="width: 20%;" class="text-right">Amount Excluding VAT (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedItems as $item)
                <tr>
                    <td>{{ $item['reference'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-center">{{ number_format($item['quantity'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No items</td>
                </tr>
            @endforelse
            @if($vat['sscl'] > 0)
            <tr>
                <td></td>
                <td><strong>SSCL {{ number_format($vat['sscl_rate'], 1) }}%</strong></td>
                <td class="text-center"></td>
                <td class="text-right"></td>
                <td class="text-right"><strong>{{ number_format($vat['sscl'], 2) }}</strong></td>
            </tr>
            @endif
            <tr>
                <td colspan="4" class="summary-label text-right">Total Value of Supply</td>
                <td class="text-right"><strong>{{ number_format($vat['supply'], 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="summary-label text-right">VAT Amount (Total Value of Supply @ {{ number_format($vat['rate'], 2) }}%)</td>
                <td class="text-right"><strong>{{ number_format($vat['vat'], 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="summary-label text-right">Total Amount / consideration including VAT</td>
                <td class="text-right"><strong>{{ number_format($vat['inclusive'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="words">
        <tr>
            <td>
                <span class="label">Total Amount in words:*</span>
                <div style="margin-top:3px;">{{ $order->amountInWords($vat['inclusive']) }}</div>
            </td>
        </tr>
    </table>

    <table class="mode">
        <tr>
            <td>
                <span class="label">Mode of Payment:*</span>
                <div style="margin-top:3px;">{{ $order->taxInvoicePaymentModeLabel() }}</div>
            </td>
        </tr>
    </table>

    <div class="pay-sign-wrap">
        <table class="sign-fields">
            <tr>
                <td class="label-cell"><span class="section-label">Signatures</span></td>
                <td>
                    <div class="office-sign-line"></div>
                    <div class="office-sign-caption">Checked by</div>
                </td>
                <td>
                    <div class="office-sign-line"></div>
                    <div class="office-sign-caption">Sale executive</div>
                </td>
                <td>
                    <div class="office-sign-line"></div>
                    <div class="office-sign-caption">Purchaser's Signature</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        * Optional fields. Tax Invoice issued under Inland Revenue VAT invoice specifications.
        @if($order->order_number)
            Order ref: {{ $order->order_number }}.
        @endif
    </div>
</body>
</html>

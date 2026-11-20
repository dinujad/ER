@php
    $subtotalVal = number_format((float) $subtotal, 2, '.', '');
    $discountType = old('discount_type', $order->discount_type ?? 'fixed');
    $discountValue = old('discount_value', $order->discount_value ?? 0);
    $transport = old('shipping', $order->shipping ?? 0);
    $otherExpenses = old('invoice_other_expenses', $order->invoice_other_expenses ?? 0);
    $claimService = app(\App\Services\SaleReturnClaimService::class);
    $availableClaim = $order->customer_id
        ? $claimService->availableCreditForCustomer($order->customer ?? $order->customer()->first())
        : 0;
    $previewNet = (float) ($order->total_amount ?? $subtotal);
    $maxClaimOnInvoice = $claimService->maxClaimForInvoiceTotal($previewNet);
    $maxClaimAllowed = min($availableClaim, $maxClaimOnInvoice);
    $showClaim = ($showClaimCredit ?? true) && ($order->customer_id || $availableClaim > 0);
    $invoiceCustomer = $order->customer ?? ($order->customer_id ? $order->customer()->first() : null);
    $customerHasTin = filled($invoiceCustomer?->tin);
    $ssclRateLabel = rtrim(rtrim(number_format((float) config('dermas.sscl_rate', 2.5), 2, '.', ''), '0'), '.');
    $vatRateLabel = rtrim(rtrim(number_format((float) config('dermas.vat_rate', 18), 2, '.', ''), '0'), '.');
    $showSsclChecked = (bool) old('invoice_show_sscl', $order->invoice_show_sscl ?? false);
    $ssclFieldId = 'invoice_show_sscl_' . uniqid();
@endphp
<div class="invoice-financial-block" data-subtotal="{{ $subtotalVal }}" data-available-claim="{{ number_format($availableClaim, 2, '.', '') }}" data-claim-rate="0.30">
    <div class="row g-2 align-items-end">
        <div class="col-md-3 col-6">
            <label class="form-label">Subtotal</label>
            <input type="text" class="form-control invoice-subtotal" value="{{ $subtotalVal }}" readonly>
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Discount Type</label>
            <select name="discount_type" class="form-select invoice-discount-type">
                <option value="fixed" @selected($discountType === 'fixed')>Fixed (Rs)</option>
                <option value="percentage" @selected($discountType === 'percentage')>Percentage (%)</option>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Discount Value</label>
            <input type="number" step="0.01" min="0" name="discount_value" class="form-control invoice-discount-value" value="{{ $discountValue }}">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Discount (Rs)</label>
            <input type="text" class="form-control invoice-discount-amount" readonly value="0.00">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Transport (Rs)</label>
            <input type="number" step="0.01" min="0" name="shipping" class="form-control invoice-transport" value="{{ $transport }}">
        </div>
    </div>
    <div class="row g-2 mt-1 align-items-end">
        <div class="col-md-3 col-6">
            <label class="form-label">Other expenses (Rs)</label>
            <input type="number" step="0.01" min="0" name="invoice_other_expenses" class="form-control invoice-other-expenses" value="{{ $otherExpenses }}">
        </div>
        @if($showClaim)
        <div class="col-md-3 col-6">
            <label class="form-label">Return claim (Rs)</label>
            <input type="number" step="0.01" min="0" max="{{ number_format($maxClaimAllowed, 2, '.', '') }}"
                   name="claim_credit_amount" class="form-control invoice-claim-credit"
                   value="{{ old('claim_credit_amount', 0) }}"
                   @if($maxClaimAllowed <= 0) readonly @endif>
            <small class="text-muted">
                Available: <strong>Rs {{ number_format($availableClaim, 2) }}</strong>
                (previous return + system return)
                · Max this invoice (30%): <strong class="invoice-max-claim">Rs {{ number_format($maxClaimOnInvoice, 2) }}</strong>
            </small>
        </div>
        @endif
        <div class="col-md-{{ $showClaim ? '6' : '9' }} col-6 text-md-end mt-2 mt-md-0">
            <strong>Net Total: Rs <span class="invoice-net-total">{{ number_format($previewNet, 2) }}</span></strong>
            <div class="small text-muted">Item total. SSCL / VAT are added on top or taken out of it as chosen below — see "Customer pays".</div>
            @if($showClaim)
                <div class="small text-muted">After claim due ↓ by claim amount (invoice total stays).</div>
            @endif
        </div>
    </div>
    @php
        $vatCode = $order->invoice_vat_code ?? \App\Support\VatTreatment::STANDARD;
        $vatMode = old('invoice_vat_mode', in_array($vatCode, ['zero', 'exempt'], true) ? $vatCode : ($order->invoice_vat_inclusive ? 'included' : 'add'));
        $ssclMode = old('invoice_sscl_mode', $order->invoice_sscl_inclusive ? 'included' : 'add');
        $vatRateCfg = (float) config('dermas.vat_rate', 18);
        $ssclRateCfg = (float) config('dermas.sscl_rate', 2.5);
        // Phase 6.4 — credit limit warning (Xero / QuickBooks style): unpaid balance on other invoices + this one.
        $creditLimit = (float) ($invoiceCustomer->credit_limit ?? 0);
        $creditWarning = null;
        if ($creditLimit > 0 && $invoiceCustomer) {
            $openOther = (float) \App\Models\Order::query()
                ->where('customer_id', $invoiceCustomer->id)
                ->where('id', '!=', $order->id)
                ->whereIn('status', [\App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_PAID, \App\Models\Order::STATUS_CREDIT])
                ->selectRaw('COALESCE(SUM(' . \App\Models\Order::invoiceTotalSql() . ' - ' . \App\Models\Order::effectivePaidAmountSubquery() . '), 0) as open_total')
                ->value('open_total');
            $thisInvoice = \App\Models\Order::computeInvoiceTax(
                $previewNet,
                in_array($vatMode, ['zero', 'exempt'], true) ? 0.0 : $vatRateCfg,
                $ssclMode === 'included',
                $vatMode === 'included'
            )['inclusive'];
            $projected = round(max(0, $openOther) + $thisInvoice, 2);
            if ($projected > $creditLimit + 0.009) {
                $creditWarning = 'Credit limit Rs ' . number_format($creditLimit, 2) . ' will be exceeded: unpaid on other invoices Rs '
                    . number_format(max(0, $openOther), 2) . ' + this invoice Rs ' . number_format($thisInvoice, 2) . ' = Rs ' . number_format($projected, 2) . '.';
            }
        }
    @endphp
    @if($creditWarning)
        <div class="alert alert-warning py-2 mt-2 mb-0 small"><i class="bi bi-exclamation-triangle"></i> {{ $creditWarning }} You can still issue it.</div>
    @endif
    <div class="row g-2 mt-2 invoice-tax-modes" data-vat-rate="{{ $vatRateCfg }}" data-sscl-rate="{{ $ssclRateCfg }}">
        <div class="col-md-6">
            <label class="form-label mb-0 fw-semibold">VAT {{ $vatRateLabel }}%</label>
            <select name="invoice_vat_mode" class="form-select form-select-sm invoice-vat-mode">
                <option value="add" @selected($vatMode === 'add')>Add VAT {{ $vatRateLabel }}% on top of item prices</option>
                <option value="included" @selected($vatMode === 'included')>Item prices already include VAT (do not add)</option>
                <option value="zero" @selected($vatMode === 'zero')>Zero rated 0% — exports / zero-rated supplies</option>
                <option value="exempt" @selected($vatMode === 'exempt')>Exempt supply — no VAT</option>
            </select>
            <div class="form-text small">
                <strong>Add on top:</strong> customer pays item total + VAT.
                <strong>Already included:</strong> customer pays only the item total; the system takes the VAT out of it and still reports it to IRD.
                <strong>Zero / Exempt:</strong> only for supplies that are legally zero-rated or exempt.
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label mb-0 fw-semibold">SSCL {{ $ssclRateLabel }}%</label>
            <select name="invoice_sscl_mode" class="form-select form-select-sm invoice-sscl-mode">
                <option value="add" @selected($ssclMode === 'add')>Add SSCL {{ $ssclRateLabel }}% on top of item prices</option>
                <option value="included" @selected($ssclMode === 'included')>Item prices already include SSCL (do not add)</option>
            </select>
            <div class="form-text small">
                <strong>Add on top:</strong> customer pays item total + SSCL (VAT is then charged on item total + SSCL).
                <strong>Already included:</strong> customer pays only the item total; SSCL is taken out of it. SSCL is still payable by the company either way.
            </div>
        </div>
        <div class="col-12">
            <div class="border rounded px-2 py-1 small bg-white invoice-tax-preview">
                Goods (excl. tax) <strong class="itp-goods">—</strong> ·
                SSCL <strong class="itp-sscl">—</strong> ·
                VAT <strong class="itp-vat">—</strong> ·
                Customer pays <strong class="itp-total">—</strong>
            </div>
        </div>
    </div>
    <script>
    (function () {
        var box = document.currentScript.previousElementSibling;
        var block = box.closest('.invoice-financial-block');
        if (!block) return;
        function r2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }
        function fmt(n) { return n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
        function calc() {
            var netEl = block.querySelector('.invoice-net-total');
            var amount = parseFloat(String(netEl ? netEl.textContent : '0').replace(/,/g, '')) || 0;
            var vm = box.querySelector('.invoice-vat-mode').value, sm = box.querySelector('.invoice-sscl-mode').value;
            var rs = parseFloat(box.dataset.ssclRate) / 100, rv = (vm === 'zero' || vm === 'exempt') ? 0 : parseFloat(box.dataset.vatRate) / 100;
            var sIn = sm === 'included', vIn = vm === 'included';
            var goods = r2(amount / ((sIn ? 1 + rs : 1) * (vIn ? 1 + rv : 1)));
            var sscl = (sIn && !vIn) ? r2(amount - goods) : r2(goods * rs);
            var supply = r2(goods + sscl);
            var vat = (sIn && vIn) ? r2(amount - supply) : r2(supply * rv);
            box.querySelector('.itp-goods').textContent = fmt(goods);
            box.querySelector('.itp-sscl').textContent = fmt(sscl);
            box.querySelector('.itp-vat').textContent = fmt(vat);
            box.querySelector('.itp-total').textContent = 'Rs ' + fmt(r2(supply + vat));
        }
        block.addEventListener('input', function () { setTimeout(calc, 0); });
        block.addEventListener('change', function () { setTimeout(calc, 0); });
        calc();
    })();
    </script>
    <div class="border rounded p-2 mt-3 bg-light">
        @if($customerHasTin)
            <div class="small">
                <strong>SSCL {{ $ssclRateLabel }}% &amp; VAT {{ $vatRateLabel }}%:</strong>
                This customer has a TIN (VAT registered), so a full <strong>TAX INVOICE</strong> is issued.
                SSCL and VAT are always shown on their own lines — there is nothing to choose here.
            </div>
        @else
            <input type="hidden" name="invoice_show_sscl" value="0">
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="invoice_show_sscl" value="1"
                       id="{{ $ssclFieldId }}" @checked($showSsclChecked)>
                <label class="form-check-label fw-semibold" for="{{ $ssclFieldId }}">
                    Show SSCL ({{ $ssclRateLabel }}%) as a separate line on the invoice
                </label>
            </div>
            <div class="small text-muted">
                This customer has no TIN, so a normal <strong>INVOICE</strong> is issued and VAT is already included in the item prices.
                SSCL {{ $ssclRateLabel }}% is <strong>always charged</strong> — this tick only changes how it is shown:
                (Applies whether SSCL is added on top or already included in the prices.)
                <ul class="mb-1 ps-3">
                    <li><strong>Ticked:</strong> item prices include VAT only. A separate “SSCL {{ $ssclRateLabel }}%” line is added under the items, equal to {{ $ssclRateLabel }}% of the goods value before tax — the same SSCL figure a tax invoice would show.</li>
                    <li><strong>Not ticked:</strong> no SSCL line. The {{ $ssclRateLabel }}% is spread across every item's unit price and amount, so the item lines alone add up to the total.</li>
                </ul>
                The final invoice total is the same either way.
            </div>
        @endif
    </div>
</div>

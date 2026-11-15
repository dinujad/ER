<script>
document.addEventListener('DOMContentLoaded', function () {
    function recalcInvoiceBlock(block) {
        var subtotal = parseFloat(block.getAttribute('data-subtotal') || block.querySelector('.invoice-subtotal')?.value || '0') || 0;
        var discountType = block.querySelector('.invoice-discount-type')?.value || 'fixed';
        var discountValue = parseFloat(block.querySelector('.invoice-discount-value')?.value || '0') || 0;
        var transport = parseFloat(block.querySelector('.invoice-transport')?.value || '0') || 0;
        var other = parseFloat(block.querySelector('.invoice-other-expenses')?.value || '0') || 0;
        var claimRate = parseFloat(block.getAttribute('data-claim-rate') || '0.3') || 0.3;
        var availableClaim = parseFloat(block.getAttribute('data-available-claim') || '0') || 0;
        var discountAmount = discountType === 'percentage'
            ? Math.min(subtotal * Math.min(discountValue, 100) / 100, subtotal)
            : Math.min(discountValue, subtotal);
        var discountAmountEl = block.querySelector('.invoice-discount-amount');
        if (discountAmountEl) discountAmountEl.value = discountAmount.toFixed(2);
        var net = Math.max(0, subtotal - discountAmount + transport + other);
        var netEl = block.querySelector('.invoice-net-total');
        if (netEl) netEl.textContent = net.toFixed(2);

        var maxOnInvoice = Math.round(net * claimRate * 100) / 100;
        var maxClaimEl = block.querySelector('.invoice-max-claim');
        if (maxClaimEl) maxClaimEl.textContent = 'Rs ' + maxOnInvoice.toFixed(2);
        var claimInput = block.querySelector('.invoice-claim-credit');
        if (claimInput) {
            var maxAllowed = Math.min(availableClaim, maxOnInvoice);
            claimInput.max = maxAllowed.toFixed(2);
            if (parseFloat(claimInput.value || '0') > maxAllowed) {
                claimInput.value = maxAllowed.toFixed(2);
            }
        }
    }

    document.querySelectorAll('.invoice-financial-block').forEach(function (block) {
        block.querySelectorAll('.invoice-discount-type, .invoice-discount-value, .invoice-transport, .invoice-other-expenses, .invoice-claim-credit').forEach(function (el) {
            el.addEventListener('input', function () { recalcInvoiceBlock(block); });
            el.addEventListener('change', function () { recalcInvoiceBlock(block); });
        });
        recalcInvoiceBlock(block);
    });
});
</script>

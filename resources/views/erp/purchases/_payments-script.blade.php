<script>
(function() {
    var paymentRowIndex = document.querySelectorAll('.payment-row').length;
    var purchaseTypeEl = document.getElementById('purchase_type');

    function chequeFieldsHtml(idx) {
        return ''
            + '<div class="payment-bank-ref mt-1 d-none">'
            + '<label class="form-label small mb-0">Transfer reference</label>'
            + '<input type="text" name="payments[' + idx + '][reference]" class="form-control form-control-sm payment-reference" placeholder="Transaction / transfer ref">'
            + '</div>'
            + '<div class="payment-cheque-fields mt-2 border-top pt-2 d-none">'
            + '<div class="small fw-semibold text-muted mb-1">Cheque details</div>'
            + '<div class="row g-2">'
            + '<div class="col-12"><label class="form-label small mb-0">Cheque number <span class="text-danger">*</span></label>'
            + '<input type="text" name="payments[' + idx + '][cheque_number]" class="form-control form-control-sm payment-cheque-number" placeholder="e.g. 000123"></div>'
            + '<div class="col-6"><label class="form-label small mb-0">Cheque date <span class="text-danger">*</span></label>'
            + '<input type="date" name="payments[' + idx + '][cheque_date]" class="form-control form-control-sm payment-cheque-date"></div>'
            + '<div class="col-6"><label class="form-label small mb-0">Issued bank <span class="text-danger">*</span></label>'
            + '<input type="text" name="payments[' + idx + '][cheque_bank]" class="form-control form-control-sm payment-cheque-bank" placeholder="Bank name"></div>'
            + '</div></div>';
    }

    function paymentRowHtml(idx) {
        return ''
            + '<div class="payment-row border rounded p-2 bg-light">'
            + '<div class="row g-2 align-items-end">'
            + '<div class="col-5"><label class="form-label small mb-0">Amount</label>'
            + '<input type="number" name="payments[' + idx + '][amount]" class="form-control form-control-sm payment-amount" step="0.01" min="0" placeholder="0.00"></div>'
            + '<div class="col-5"><label class="form-label small mb-0">Method</label>'
            + '<select name="payments[' + idx + '][payment_method]" class="form-select form-select-sm payment-method">'
            + '<option value="">—</option><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="bank_transfer">Bank Transfer</option>'
            + '</select></div>'
            + '<div class="col-2 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-payment-row" title="Remove">&times;</button></div>'
            + '</div>' + chequeFieldsHtml(idx) + '</div>';
    }

    function togglePaymentMethodFields(row) {
        var method = row.querySelector('.payment-method').value;
        var bankRef = row.querySelector('.payment-bank-ref');
        var chequeBox = row.querySelector('.payment-cheque-fields');
        if (bankRef) bankRef.classList.toggle('d-none', method !== 'bank_transfer');
        if (chequeBox) chequeBox.classList.toggle('d-none', method !== 'cheque');
    }

    function bindPaymentRow(row) {
        var methodSel = row.querySelector('.payment-method');
        if (methodSel) {
            methodSel.addEventListener('change', function() {
                togglePaymentMethodFields(row);
                calcPaymentsSummary();
            });
            togglePaymentMethodFields(row);
        }
        row.querySelectorAll('.payment-amount').forEach(function(el) {
            el.addEventListener('input', calcPaymentsSummary);
        });
        var rm = row.querySelector('.remove-payment-row');
        if (rm) {
            rm.addEventListener('click', function() {
                var list = document.getElementById('payments-list');
                if (list.querySelectorAll('.payment-row').length <= 1) {
                    row.querySelector('.payment-amount').value = '';
                    if (methodSel) methodSel.value = '';
                    row.querySelectorAll('input[type="text"], input[type="date"]').forEach(function(i) { i.value = ''; });
                    togglePaymentMethodFields(row);
                    calcPaymentsSummary();
                    return;
                }
                row.remove();
                calcPaymentsSummary();
            });
        }
    }

    window.calcPaymentsSummary = function() {
        var total = parseFloat(document.getElementById('sum-total').textContent) || 0;
        var paid = 0;
        document.querySelectorAll('.payment-amount').forEach(function(inp) {
            paid += parseFloat(inp.value) || 0;
        });
        paid = Math.round(paid * 100) / 100;
        var balanceEl = document.getElementById('sum-balance');
        var paidEl = document.getElementById('sum-paid');
        if (paidEl) paidEl.textContent = paid.toFixed(2);
        if (balanceEl) balanceEl.textContent = (total - paid).toFixed(2);
        var hint = document.getElementById('cash-type-hint');
        if (hint && purchaseTypeEl) hint.classList.toggle('d-none', purchaseTypeEl.value !== 'cash');
    };

    window.syncCashPurchaseType = function() {
        calcPaymentsSummary();
    };

    document.querySelectorAll('.payment-row').forEach(bindPaymentRow);

    var addBtn = document.getElementById('add-payment-row');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var list = document.getElementById('payments-list');
            var wrap = document.createElement('div');
            wrap.innerHTML = paymentRowHtml(paymentRowIndex);
            var row = wrap.firstElementChild;
            list.appendChild(row);
            paymentRowIndex++;
            bindPaymentRow(row);
            calcPaymentsSummary();
        });
    }

    if (purchaseTypeEl) {
        purchaseTypeEl.addEventListener('change', calcPaymentsSummary);
    }

    calcPaymentsSummary();
})();
</script>

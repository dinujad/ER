<script>
(function(){
    var form = document.getElementById('multi-payment-form');
    var container = document.getElementById('payment-lines');
    var addBtn = document.getElementById('add-payment-row');
    var template = document.getElementById('payment-line-template');
    var totalEl = document.getElementById('payment-entering-total');
    var settlementTotalEl = document.getElementById('payment-settlement-total');
    var saveBtn = document.getElementById('save-payments-btn');
    if (!form || !container || !addBtn || !template) return;

    var dueAmount = parseFloat(container.getAttribute('data-due') || '0');
    var chequePolicy = {
        min: @json($chequeMin),
        maxNormal: @json($chequeMaxNormal),
        maxException: @json($chequeMaxException),
        minException: @json($chequeMinException)
    };

    function replaceIndexPlaceholders(root, idx) {
        var indexStr = String(idx);
        root.querySelectorAll('[name]').forEach(function(el) {
            if (el.name) el.name = el.name.replace(/__INDEX__/g, indexStr);
            if (el.id) el.id = el.id.replace(/__INDEX__/g, indexStr);
        });
        root.querySelectorAll('label[for]').forEach(function(el) {
            if (el.htmlFor) el.htmlFor = el.htmlFor.replace(/__INDEX__/g, indexStr);
        });
        root.setAttribute('data-index', indexStr);
    }

    function nextIndex() {
        var max = -1;
        container.querySelectorAll('.payment-line-row').forEach(function(row) {
            var i = parseInt(row.getAttribute('data-index'), 10);
            if (!isNaN(i) && i > max) max = i;
        });
        return max + 1;
    }

    function reindexRows() {
        container.querySelectorAll('.payment-line-row').forEach(function(row, idx) {
            row.setAttribute('data-index', idx);
            row.querySelectorAll('[name]').forEach(function(el) {
                el.name = el.name.replace(/payments\[\d+\]/, 'payments[' + idx + ']');
                if (el.id) el.id = el.id.replace(/payments_\d+_/g, 'payments_' + idx + '_');
            });
            row.querySelectorAll('label[for]').forEach(function(el) {
                el.htmlFor = el.htmlFor.replace(/payments_\d+_/g, 'payments_' + idx + '_');
            });
            var removeBtn = row.querySelector('.remove-payment-row');
            if (removeBtn) {
                removeBtn.style.display = container.querySelectorAll('.payment-line-row').length > 1 ? '' : 'none';
            }
        });
    }

    function computeLineTotals(settlementBase, discountType, discountValue) {
        settlementBase = Math.max(0, settlementBase);
        discountValue = Math.max(0, discountValue || 0);
        if (discountValue <= 0 || settlementBase <= 0) {
            return { discountAmount: 0, netAmount: settlementBase };
        }
        var discountAmount = 0;
        if (discountType === 'percentage') {
            discountValue = Math.min(100, discountValue);
            discountAmount = Math.round(settlementBase * discountValue / 100 * 100) / 100;
        } else {
            discountAmount = Math.min(discountValue, settlementBase);
            discountAmount = Math.round(discountAmount * 100) / 100;
        }
        var netAmount = Math.max(0, Math.round((settlementBase - discountAmount) * 100) / 100);
        return { discountAmount: discountAmount, netAmount: netAmount };
    }

    function currentClaim() {
        var box = document.getElementById('claim_return');
        var input = document.getElementById('claim_amount');
        if (!box || !box.checked || !input) return 0;
        var value = parseFloat(input.value || '0');
        return !isNaN(value) && value > 0 ? value : 0;
    }

    function transportAmount() {
        var input = document.getElementById('transport_amount');
        var value = parseFloat(input && input.value ? input.value : '0');
        return !isNaN(value) && value > 0 ? value : 0;
    }

    function payableDue() {
        return Math.max(0, Math.round((dueAmount - currentClaim() - transportAmount()) * 100) / 100);
    }

    function remainingDueForRow(excludeRow) {
        var used = 0;
        container.querySelectorAll('.payment-line-row').forEach(function(row) {
            if (row === excludeRow) return;
            recalcRow(row);
            used += rowDueReduction(row);
        });
        return Math.max(0, Math.round((payableDue() - used) * 100) / 100);
    }

    function recalcRow(row) {
        var method = row.querySelector('.payment-method-select');
        var isCash = !method || method.value === 'cash';
        var settlementInput = row.querySelector('.payment-settlement-input');
        var discountTypeEl = row.querySelector('.payment-discount-type');
        var discountValueEl = row.querySelector('.payment-discount-value');
        var discountAmountEl = row.querySelector('.payment-discount-amount');
        var amountDisplayEl = row.querySelector('.payment-amount-display');
        var baseLabel = row.querySelector('.payment-base-label');
        var nextHint = row.querySelector('.payment-next-hint');

        var entered = parseFloat(settlementInput && settlementInput.value ? settlementInput.value : '0');
        if (isNaN(entered)) entered = 0;
        var discountType = discountTypeEl ? discountTypeEl.value : 'fixed';
        var discountValue = parseFloat(discountValueEl && discountValueEl.value ? discountValueEl.value : '0');
        if (isNaN(discountValue)) discountValue = 0;

        var totals = computeLineTotals(entered, discountType, discountValue);
        var dueReduction = isCash
            ? Math.round((entered + totals.discountAmount) * 100) / 100
            : entered;
        var received = isCash ? entered : totals.netAmount;
        if (discountAmountEl) discountAmountEl.value = totals.discountAmount.toFixed(2);
        if (amountDisplayEl) amountDisplayEl.value = received.toFixed(2);
        row.setAttribute('data-due-reduction', String(dueReduction));
        if (baseLabel) baseLabel.textContent = isCash ? 'Cash given (Rs)' : 'Apply to due (Rs)';
        if (nextHint) {
            if (isCash && totals.discountAmount > 0) {
                nextHint.textContent = 'Next payment reduced by Rs ' + totals.discountAmount.toFixed(2)
                    + '. Due reduced by Rs ' + dueReduction.toFixed(2) + '.';
                nextHint.classList.remove('d-none');
            } else {
                nextHint.textContent = '';
                nextHint.classList.add('d-none');
            }
        }
    }

    function rowDueReduction(row) {
        var value = parseFloat(row.getAttribute('data-due-reduction') || '0');
        return !isNaN(value) && value > 0 ? value : 0;
    }

    function setChequeFieldsEnabled(row, enabled) {
        row.querySelectorAll('.cheque-fields input, .cheque-fields textarea, .cheque-fields select').forEach(function(el) {
            el.disabled = !enabled;
            if (el.type === 'file') {
                el.required = false;
            } else if (el.type === 'checkbox') {
                el.required = false;
            } else if (el.classList.contains('cheque-date-input')) {
                el.required = enabled;
            }
        });
    }

    function applyChequeDateLimits(row) {
        var chequeDate = row.querySelector('.cheque-date-input');
        var exceptionCb = row.querySelector('.cheque-exception-input');
        if (!chequeDate || chequeDate.disabled) return;
        var ex = exceptionCb && exceptionCb.checked;
        chequeDate.min = ex ? chequePolicy.minException : chequePolicy.min;
        chequeDate.max = ex ? chequePolicy.maxException : chequePolicy.maxNormal;
    }

    function syncChequeRow(row) {
        var method = row.querySelector('.payment-method-select');
        var chequeBlock = row.querySelector('.cheque-fields');
        if (!method || !chequeBlock) return;
        var isCheque = method.value === 'cheque';
        chequeBlock.style.display = isCheque ? 'block' : 'none';
        setChequeFieldsEnabled(row, isCheque);
        if (isCheque) {
            var chequeDate = row.querySelector('.cheque-date-input');
            if (chequeDate && !chequeDate.value) chequeDate.value = chequePolicy.min;
            applyChequeDateLimits(row);
        }
    }

    function updateTotal() {
        var settlementSum = 0;
        var receivedSum = 0;
        container.querySelectorAll('.payment-line-row').forEach(function(row) {
            recalcRow(row);
            var amountDisplay = row.querySelector('.payment-amount-display');
            var s = rowDueReduction(row);
            var r = parseFloat(amountDisplay && amountDisplay.value ? amountDisplay.value : '0');
            if (s > 0) settlementSum += s;
            if (!isNaN(r) && r > 0) receivedSum += r;
        });
        var transport = transportAmount();
        if (transport > 0) {
            settlementSum += transport;
            receivedSum += transport;
        }
        var transportRow = document.getElementById('payment-transport-row');
        var transportTotalEl = document.getElementById('payment-transport-total');
        if (transportRow) transportRow.classList.toggle('d-none', !(transport > 0));
        if (transportRow) transportRow.classList.toggle('d-flex', transport > 0);
        if (transportTotalEl) transportTotalEl.textContent = transport.toFixed(2);
        if (settlementTotalEl) {
            settlementTotalEl.textContent = settlementSum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            settlementTotalEl.classList.toggle('text-danger', settlementSum > dueAmount + 0.009);
        }
        if (totalEl) {
            totalEl.textContent = receivedSum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function bindRow(row) {
        var method = row.querySelector('.payment-method-select');
        var exceptionCb = row.querySelector('.cheque-exception-input');
        var settlementInput = row.querySelector('.payment-settlement-input');
        var discountTypeEl = row.querySelector('.payment-discount-type');
        var discountValueEl = row.querySelector('.payment-discount-value');
        var removeBtn = row.querySelector('.remove-payment-row');

        if (method) {
            method.addEventListener('change', function() {
                syncChequeRow(row);
                if (settlementInput && (!settlementInput.value || parseFloat(settlementInput.value) <= 0)) {
                    settlementInput.value = remainingDueForRow(row).toFixed(2);
                }
                recalcRow(row);
                updateTotal();
            });
        }
        if (exceptionCb) exceptionCb.addEventListener('change', function(){ applyChequeDateLimits(row); });
        [settlementInput, discountTypeEl, discountValueEl].forEach(function(el) {
            if (el) {
                el.addEventListener('input', updateTotal);
                el.addEventListener('change', updateTotal);
            }
        });
        if (removeBtn) removeBtn.addEventListener('click', function(){
            row.remove();
            reindexRows();
            updateTotal();
        });
        syncChequeRow(row);
        recalcRow(row);
    }

    function addRow() {
        var idx = nextIndex();
        var row;
        if (template.content && template.content.firstElementChild) {
            row = template.content.firstElementChild.cloneNode(true);
        } else {
            var wrap = document.createElement('div');
            wrap.innerHTML = template.innerHTML.trim();
            row = wrap.firstElementChild;
        }
        if (!row) return;
        replaceIndexPlaceholders(row, idx);
        container.appendChild(row);
        reindexRows();
        bindRow(row);
        var settlementInput = row.querySelector('.payment-settlement-input');
        if (settlementInput) settlementInput.value = remainingDueForRow(row).toFixed(2);
        updateTotal();
    }

    container.querySelectorAll('.payment-line-row').forEach(function(row) {
        bindRow(row);
        var settlementInput = row.querySelector('.payment-settlement-input');
        if (settlementInput && (!settlementInput.value || parseFloat(settlementInput.value) <= 0)) {
            settlementInput.value = remainingDueForRow(row).toFixed(2);
        }
    });
    updateTotal();
    addBtn.addEventListener('click', addRow);

    var claimToggle = document.getElementById('claim_return');
    var claimInput = document.getElementById('claim_amount');
    var claimFields = document.getElementById('claim_return_fields');
    function syncClaimFields() {
        if (!claimFields || !claimToggle) return;
        claimFields.classList.toggle('d-none', !claimToggle.checked);
        var rows = container.querySelectorAll('.payment-line-row');
        if (rows.length === 1) {
            var settlementInput = rows[0].querySelector('.payment-settlement-input');
            if (settlementInput) {
                var next = payableDue();
                settlementInput.value = next > 0 ? next.toFixed(2) : '';
                recalcRow(rows[0]);
                updateTotal();
            }
        }
    }
    if (claimToggle) claimToggle.addEventListener('change', syncClaimFields);
    if (claimInput) claimInput.addEventListener('input', syncClaimFields);
    var transportInput = document.getElementById('transport_amount');
    function fitSinglePaymentRow() {
        var rows = container.querySelectorAll('.payment-line-row');
        if (rows.length !== 1) {
            updateTotal();
            return;
        }
        var settlementInput = rows[0].querySelector('.payment-settlement-input');
        if (!settlementInput) return;
        var next = payableDue();
        var current = parseFloat(settlementInput.value || '0');
        if (!(current > 0) || current > next + 0.009) {
            settlementInput.value = next > 0 ? next.toFixed(2) : '';
            recalcRow(rows[0]);
        }
        updateTotal();
    }
    if (transportInput) {
        transportInput.addEventListener('input', fitSinglePaymentRow);
        transportInput.addEventListener('change', fitSinglePaymentRow);
    }

    form.addEventListener('submit', function(e) {
        container.querySelectorAll('.payment-line-row').forEach(function(row) {
            var settlementInput = row.querySelector('.payment-settlement-input');
            var settlement = parseFloat(settlementInput && settlementInput.value ? settlementInput.value : '0');
            if (!(settlement > 0)) {
                row.remove();
            } else {
                recalcRow(row);
                syncChequeRow(row);
            }
        });
        reindexRows();

        var claimAmount = currentClaim();
        var claimBox = document.getElementById('claim_return');
        if (claimBox && claimBox.checked && !(claimAmount > 0)) {
            e.preventDefault();
            alert('Enter the return credit amount to claim.');
            return;
        }

        var transport = transportAmount();
        if (!container.querySelector('.payment-line-row') && !(claimAmount > 0) && !(transport > 0)) {
            e.preventDefault();
            alert('Add at least one payment row with a settlement amount.');
            addRow();
            return;
        }

        var settlementSum = transport;
        container.querySelectorAll('.payment-line-row').forEach(function(row) {
            recalcRow(row);
            settlementSum += rowDueReduction(row);
        });
        if (settlementSum + claimAmount > dueAmount + 0.009) {
            e.preventDefault();
            alert('Cash plus the discount taken off the next payment cannot exceed the amount still due (Rs ' + dueAmount.toFixed(2) + ').');
            return;
        }

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
        }
    });
})();
</script>

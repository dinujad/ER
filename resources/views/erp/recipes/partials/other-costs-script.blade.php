<script>
    document.addEventListener('DOMContentLoaded', function() {
        const initialOtherCosts = @json($initialOtherCosts ?? []);
        const tbody = document.getElementById('otherCostsBody');
        const addBtn = document.getElementById('addOtherCostBtn');
        const totalEl = document.getElementById('otherCostsTotal');
        let otherCostCount = 0;

        function updateOtherCostsTotal() {
            let total = 0;
            tbody.querySelectorAll('.other-cost-amount').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            totalEl.textContent = total.toFixed(2);
        }

        function addOtherCostRow(data = null) {
            const rowId = otherCostCount++;
            const tr = document.createElement('tr');

            const descTd = document.createElement('td');
            const descInput = document.createElement('input');
            descInput.type = 'text';
            descInput.name = `other_costs[${rowId}][description]`;
            descInput.className = 'form-control form-control-sm';
            descInput.placeholder = 'e.g. Sewing, Transport, Labour';
            if (data && data.description) {
                descInput.value = data.description;
            }
            descTd.appendChild(descInput);

            const amountTd = document.createElement('td');
            const amountInput = document.createElement('input');
            amountInput.type = 'number';
            amountInput.step = '0.01';
            amountInput.min = '0';
            amountInput.name = `other_costs[${rowId}][amount]`;
            amountInput.className = 'form-control form-control-sm other-cost-amount';
            amountInput.placeholder = '0.00';
            if (data && data.amount != null) {
                amountInput.value = data.amount;
            }
            amountTd.appendChild(amountInput);

            const removeTd = document.createElement('td');
            removeTd.className = 'text-center';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-link text-danger mb-0 btn-remove-other-cost';
            removeBtn.title = 'Remove row';
            removeBtn.textContent = '×';
            removeTd.appendChild(removeBtn);

            tr.appendChild(descTd);
            tr.appendChild(amountTd);
            tr.appendChild(removeTd);
            tbody.appendChild(tr);
            updateOtherCostsTotal();
        }

        addBtn.addEventListener('click', () => addOtherCostRow());

        tbody.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-other-cost')) {
                e.target.closest('tr').remove();
                updateOtherCostsTotal();
            }
        });

        tbody.addEventListener('input', function(e) {
            if (e.target.classList.contains('other-cost-amount')) {
                updateOtherCostsTotal();
            }
        });

        if (initialOtherCosts.length) {
            initialOtherCosts.forEach(row => addOtherCostRow(row));
        }
    });
</script>

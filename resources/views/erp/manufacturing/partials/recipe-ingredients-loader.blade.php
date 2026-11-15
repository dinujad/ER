{{-- Requires: #job_product_id, #total_cut_pieces, #ingredients-table-body, #stage-name, #ingredients-section --}}
<script>
function loadRecipeIngredients(productId) {
    const tbody = document.getElementById('ingredients-table-body');
    const totalCut = parseFloat(document.getElementById('total_cut_pieces')?.value || '0') || 0;
    const stageNameSpan = document.getElementById('stage-name');

    if (!productId) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No product on job.</td></tr>';
        return;
    }

    fetch(`{{ url('/erp/recipes/by-product') }}/${productId}`)
        .then(response => {
            if (!response.ok) throw new Error('Recipe not found');
            return response.json();
        })
        .then(recipe => {
            if (!tbody) return;
            tbody.innerHTML = '';
            let hasIngredients = false;

            (recipe.steps || []).forEach(step => {
                if (!step.ingredients?.length) return;
                hasIngredients = true;
                if (stageNameSpan) stageNameSpan.textContent = step.name;

                step.ingredients.forEach(ing => {
                    const requiredPerUnit = parseFloat(ing.quantity);
                    const totalNeed = (requiredPerUnit * totalCut).toFixed(2);
                    const stock = ing.material ? ing.material.quantity : 0;
                    const matId = ing.material_id;

                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td><h6 class="mb-0 text-sm">${ing.material ? ing.material.name : 'Unknown'}</h6></td>
                            <td><span class="text-xs font-weight-bold">${requiredPerUnit}</span></td>
                            <td><span class="text-xs font-weight-bold text-primary">${totalNeed}</span></td>
                            <td><span class="text-xs font-weight-bold">${stock}</span></td>
                            <td>
                                <input type="hidden" name="materials[${matId}][id]" value="${matId}">
                                <input type="number" step="0.01" name="materials[${matId}][issue_qty]"
                                    class="form-control border px-2 py-1" value="${totalNeed}" min="0" max="${stock}">
                            </td>
                        </tr>`);
                });
            });

            if (!hasIngredients) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No ingredients in recipe.</td></tr>';
                if (stageNameSpan) stageNameSpan.textContent = 'No ingredients';
            }
        })
        .catch(() => {
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load recipe.</td></tr>';
            if (stageNameSpan) stageNameSpan.textContent = 'Error';
        });
}
</script>

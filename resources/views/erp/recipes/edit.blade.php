@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @canRoute('product-recipes.update')
<form action="{{ route('product-recipes.update', $productRecipe->id) }}" method="POST" id="recipeForm">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 d-flex justify-content-between">
                        <h6>Edit Product Recipe</h6>
                        <button type="submit" class="btn btn-success btn-sm">Update Recipe</button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label>Recipe Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $productRecipe->name) }}" required>
                                <small class="text-muted text-xs">Product: {{ $productRecipe->product->name ?? 'N/A' }}</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label>Instructions / Notes</label>
                                <textarea name="instructions" class="form-control" rows="2">{{ old('instructions', $productRecipe->instructions) }}</textarea>
                            </div>
                        </div>

                        <hr>
                        @include('erp.recipes.partials.other-costs-section')

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Production Steps</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addStepBtn">Add Production Step</button>
                        </div>

                        <div id="stepsContainer"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endcanRoute
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const materials = @json($materials);
        const stages = @json($stages);
        const existingSteps = @json($productRecipe->steps);
        const stepsContainer = document.getElementById('stepsContainer');
        const addStepBtn = document.getElementById('addStepBtn');
        let stepCount = 0;

        function getMaterialOptions(selectedId = null) {
            let options = '<option value="">Select Ingredient</option>';
            materials.forEach(m => {
                const selected = selectedId && String(selectedId) === String(m.id) ? ' selected' : '';
                options += `<option value="${m.id}" data-price="${m.unit_price || 0}"${selected}>${m.name} (${m.unit})</option>`;
            });
            return options;
        }

        function getStageOptions() {
            let options = '<option value="">Select Stage</option>';
            stages.forEach(s => {
                options += `<option value="${s.name}" data-stage-id="${s.id}">${s.name}</option>`;
            });
            return options;
        }

        function updateRowCost(row) {
            const select = row.querySelector('.material-select');
            const qtyInput = row.querySelector('.qty-input');
            const costDisplay = row.querySelector('.cost-display');
            if (!select || !qtyInput || !costDisplay) return;

            if (select.selectedIndex > 0 && qtyInput.value) {
                const price = parseFloat(select.options[select.selectedIndex].dataset.price) || 0;
                const qty = parseFloat(qtyInput.value) || 0;
                costDisplay.textContent = (price * qty).toFixed(2);
            }
        }

        function addIngredientRow(stepId, ingredientData = null) {
            const tbody = document.querySelector(`#table-step-${stepId} tbody`);
            const ingCount = tbody.children.length;
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    <select name="steps[${stepId}][ingredients][${ingCount}][material_id]" class="form-select form-select-sm material-select" required>
                        ${getMaterialOptions(ingredientData ? ingredientData.material_id : null)}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.0001" name="steps[${stepId}][ingredients][${ingCount}][quantity]" class="form-control form-control-sm qty-input" placeholder="Qty" required value="${ingredientData ? ingredientData.quantity : ''}">
                </td>
                <td>
                    <span class="cost-display text-xs font-weight-bold">0.00</span>
                </td>
                <td>
                    <button type="button" class="btn btn-link text-danger mb-0 remove-ing-btn"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(row);
            updateRowCost(row);
        }

        function addStepCard(stepData = null) {
            const stepId = stepCount++;

            const stepHtml = `
                <div class="card mb-3 border step-card" id="step-${stepId}">
                    <div class="card-header bg-light p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2 flex-grow-1 align-items-center">
                            <span class="badge bg-secondary">Step ${stepId + 1}</span>
                            <input type="hidden" name="steps[${stepId}][production_stage_id]" class="step-stage-id" id="stage-id-${stepId}" value="">
                            <select name="steps[${stepId}][name]" class="form-select form-select-sm step-stage-select" style="max-width: 250px;" required data-step-id="${stepId}">
                                ${getStageOptions()}
                            </select>
                            <input type="text" name="steps[${stepId}][description]" class="form-control form-control-sm step-description" placeholder="Additional Notes (Optional)" style="max-width: 300px;">
                        </div>
                        <button type="button" class="btn btn-danger btn-sm mb-0 remove-step-btn" onclick="removeStep(${stepId})">Remove Step</button>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0 ingredient-table" id="table-step-${stepId}">
                                <thead>
                                    <tr>
                                        <th style="width: 40%">Ingredient</th>
                                        <th style="width: 20%">Quantity</th>
                                        <th style="width: 15%">Cost</th>
                                        <th style="width: 10%"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2 btn-add-ing" data-step="${stepId}">+ Add Ingredient</button>
                    </div>
                </div>
            `;

            stepsContainer.insertAdjacentHTML('beforeend', stepHtml);

            if (stepData) {
                const card = document.getElementById(`step-${stepId}`);
                const stageSelect = card.querySelector('.step-stage-select');
                const descInput = card.querySelector('.step-description');
                const stageIdInput = document.getElementById(`stage-id-${stepId}`);

                if (stepData.production_stage_id) {
                    stageIdInput.value = stepData.production_stage_id;
                }

                for (let i = 0; i < stageSelect.options.length; i++) {
                    if (stageSelect.options[i].value === stepData.name) {
                        stageSelect.selectedIndex = i;
                        if (!stageIdInput.value && stageSelect.options[i].dataset.stageId) {
                            stageIdInput.value = stageSelect.options[i].dataset.stageId;
                        }
                        break;
                    }
                }

                if (stepData.description) {
                    descInput.value = stepData.description;
                }

                (stepData.ingredients || []).forEach(ing => addIngredientRow(stepId, ing));
            }

            return stepId;
        }

        addStepBtn.addEventListener('click', () => addStepCard());

        stepsContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('step-stage-select')) {
                const opt = e.target.options[e.target.selectedIndex];
                const stageId = opt && opt.dataset.stageId ? opt.dataset.stageId : '';
                const stepId = e.target.dataset.stepId;
                const hid = document.getElementById('stage-id-' + stepId);
                if (hid) hid.value = stageId;
            }

            if (e.target.classList.contains('material-select') || e.target.classList.contains('qty-input')) {
                updateRowCost(e.target.closest('tr'));
            }
        });

        stepsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-add-ing')) {
                addIngredientRow(e.target.getAttribute('data-step'));
            }

            if (e.target.closest('.remove-ing-btn')) {
                e.target.closest('tr').remove();
            }
        });

        window.removeStep = function(id) {
            if (!confirm('Remove this step?')) return;
            document.getElementById(`step-${id}`).remove();
        };

        if (existingSteps.length) {
            existingSteps.forEach(step => addStepCard(step));
        } else {
            addStepCard();
        }
    });
</script>

@include('erp.recipes.partials.other-costs-script')
@endsection

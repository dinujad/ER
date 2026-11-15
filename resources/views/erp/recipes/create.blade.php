@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('product-recipes.store') }}" method="POST" id="recipeForm">
        @csrf
        <!-- ... [keep previous content unchanged] ... -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0 d-flex justify-content-between">
                        <h6>Create Product Recipe</h6>
                        <button type="submit" class="btn btn-success btn-sm">Save Recipe</button>
                    </div>
                    <div class="card-body">
                        <!-- Product & Basic Info -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label>Recipe Name (Product Name)</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Standard Batch" required>
                                <small class="text-muted text-xs">A new product will be automatically created with this name.</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label>Instructions / Notes</label>
                                <textarea name="instructions" class="form-control" rows="2">{{ old('instructions') }}</textarea>
                            </div>
                        </div>

                        <hr>
                        @include('erp.recipes.partials.other-costs-section')

                        <hr>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Production Steps</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addStepBtn">Add Production Step</button>
                        </div>

                        <div id="stepsContainer">
                            <!-- Steps added here -->
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const materials = @json($materials);
        const stages = @json($stages);
        const stepsContainer = document.getElementById('stepsContainer');
        const addStepBtn = document.getElementById('addStepBtn');
        let stepCount = 0;

        function getMaterialOptions() {
            let options = '<option value="">Select Ingredient</option>';
            materials.forEach(m => {
                options += `<option value="${m.id}" data-price="${m.unit_price || 0}">${m.name} (${m.unit})</option>`;
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

        addStepBtn.addEventListener('click', function() {
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
                            <input type="text" name="steps[${stepId}][description]" class="form-control form-control-sm" placeholder="Additional Notes (Optional)" style="max-width: 300px;">
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
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2 btn-add-ing" data-step="${stepId}">+ Add Ingredient</button>
                    </div>
                </div>
            `;
            
            stepsContainer.insertAdjacentHTML('beforeend', stepHtml);
        });
        stepsContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('step-stage-select')) {
                const opt = e.target.options[e.target.selectedIndex];
                const stageId = opt && opt.dataset.stageId ? opt.dataset.stageId : '';
                const stepId = e.target.dataset.stepId;
                const hid = document.getElementById('stage-id-' + stepId);
                if (hid) hid.value = stageId;
            }
        });

        stepsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-add-ing')) {
                const stepId = e.target.getAttribute('data-step');
                const tbody = document.querySelector(`#table-step-${stepId} tbody`);
                const ingCount = tbody.children.length;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <select name="steps[${stepId}][ingredients][${ingCount}][material_id]" class="form-select form-select-sm material-select" required>
                            ${getMaterialOptions()}
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.0001" name="steps[${stepId}][ingredients][${ingCount}][quantity]" class="form-control form-control-sm qty-input" placeholder="Qty" required>
                    </td>
                    <td>
                        <span class="cost-display text-xs font-weight-bold">0.00</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-link text-danger mb-0 remove-ing-btn"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(row);
            }

            if (e.target.closest('.remove-ing-btn')) {
                e.target.closest('tr').remove();
            }
        });

        // Cost Calculation (Delegated)
        stepsContainer.addEventListener('change', function(e) {
             if (e.target.classList.contains('material-select') || e.target.classList.contains('qty-input')) {
                const row = e.target.closest('tr');
                const select = row.querySelector('.material-select');
                const qtyInput = row.querySelector('.qty-input');
                const costDisplay = row.querySelector('.cost-display');

                if (select.selectedIndex > 0 && qtyInput.value) {
                    const price = parseFloat(select.options[select.selectedIndex].dataset.price) || 0;
                    const qty = parseFloat(qtyInput.value) || 0;
                    const total = price * qty;
                    costDisplay.textContent = total.toFixed(2);
                }
            }
        });

        window.removeStep = function(id) {
            if(!confirm('Remove this step?')) return;
            document.getElementById(`step-${id}`).remove();
        }

        addStepBtn.click(); // Add initial step
        
        // Product Modal Logic (Keep existing)
        document.getElementById('saveProductBtn').addEventListener('click', function() {
            const name = document.getElementById('newProductName').value;
            if(!name) return alert('Please enter a name');

            fetch('{{ route("products.ajax.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name: name })
            })
            .then(res => res.json())
            .then(data => {
                if(data.id) {
                    const select = document.querySelector('select[name="product_id"]');
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.text = data.name;
                    opt.selected = true;
                    select.add(opt);
                    
                    const modalEl = document.getElementById('createProductModal');
                    bootstrap.Modal.getInstance(modalEl).hide();
                    document.getElementById('newProductName').value = '';
                } else {
                    alert('Error creating product');
                }
            });
        });
    });
</script>

@include('erp.recipes.partials.other-costs-script')

<!-- Create Product Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label>Product Name</label>
                <input type="text" id="newProductName" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
            </div>
        </div>
    </div>
</div>
@endsection

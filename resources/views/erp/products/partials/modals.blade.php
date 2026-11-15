<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('categories.store') }}" method="POST" class="inline-form" data-target="category_select">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Parent</label>
                         <select name="parent_id" class="form-select">
                            <option value="">None</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('brands.store') }}" method="POST" class="inline-form" data-target="brand_select">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Brand</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('units.store') }}" method="POST" class="inline-form" data-target="unit_select">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Short Name</label>
                        <input type="text" name="short_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Unit</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- JS for Inline Forms works if we handle it generically or just let it reload for now. 
     For UltimatePOS style, it should ideally use AJAX. 
     I'll implemented a basic non-ajax submit for now or advanced ajax later. 
     Let's stick to standard submit for simplicity unless requested otherwise, 
     but the user asked for "without leaving the form". So AJAX is required. --}}

<script>
    document.querySelectorAll('.inline-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            let targetSelectId = this.getAttribute('data-target');
            let action = this.getAttribute('action');
            let modalEl = this.closest('.modal');
            let modalInstance = bootstrap.Modal.getInstance(modalEl);

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        const firstError = err?.errors ? Object.values(err.errors)[0]?.[0] : null;
                        throw new Error(firstError || err?.message || 'Failed to save.');
                    });
                }
                return response.json();
            })
            .then(data => {
                const targetSelect = document.getElementById(targetSelectId);
                if (!targetSelect) {
                    throw new Error('Target select not found.');
                }

                if (!data?.id || !data?.name) {
                    throw new Error(data?.message || 'Invalid response from server.');
                }

                const existingOption = targetSelect.querySelector(`option[value="${data.id}"]`);
                if (!existingOption) {
                    const option = new Option(data.name, data.id, true, true);
                    targetSelect.add(option);
                } else {
                    existingOption.selected = true;
                }

                if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(targetSelect).hasClass('select2-hidden-accessible')) {
                    jQuery(targetSelect).trigger('change');
                } else {
                    targetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                modalInstance.hide();
                this.reset();
                alert(data.message || 'Saved successfully.');
            })
            .catch(error => {
                console.error(error);
                alert(error.message || 'Save failed. Please try again.');
            });
        });
    });
</script>

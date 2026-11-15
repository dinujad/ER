{{-- Sales person expense form — pass optional $redirectTo --}}
<div class="card border-primary mb-3" id="expenses">
    <div class="card-header bg-primary text-white py-2">
        <i class="bi bi-receipt me-2"></i>Add expense
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('sales-person.expenses.store') }}" enctype="multipart/form-data">
            @csrf
            @if(!empty($redirectTo))
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
            @endif
            <div class="row g-2 g-md-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small mb-1">Date <span class="text-danger">*</span></label>
                    <input type="date" name="expense_date" class="form-control form-control-sm @error('expense_date') is-invalid @enderror"
                        value="{{ old('expense_date', date('Y-m-d')) }}" required>
                    @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small mb-1">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select form-select-sm sp-expense-type @error('type') is-invalid @enderror" required>
                        @foreach(\App\Models\SalesPersonExpense::typeOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Description <span class="text-danger">*</span></label>
                    <input type="text" name="description" class="form-control form-control-sm sp-expense-desc @error('description') is-invalid @enderror"
                        value="{{ old('description') }}" placeholder="e.g. fuel, lunch" required maxlength="150">
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Amount (Rs) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm @error('amount') is-invalid @enderror"
                        value="{{ old('amount') }}" placeholder="0.00" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Slip</label>
                    <input type="file" name="slip" class="form-control form-control-sm @error('slip') is-invalid @enderror"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif,image/*,application/pdf"
                        capture="environment">
                    <div class="form-text small">JPG, PNG, PDF (max 10MB)</div>
                    @error('slip')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Notes</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes') }}" placeholder="Optional">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto">
                        <i class="bi bi-plus-circle me-1"></i> Save expense
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

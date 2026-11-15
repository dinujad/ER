{{-- @var \App\Models\VehicleDailyRun $run --}}
<div class="card mb-3 border-primary" id="expenses">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt me-2"></i>Add expense</span>
        <span class="badge bg-light text-primary">Job #{{ $run->id }}</span>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('driver.add-item') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="vehicle_daily_run_id" value="{{ $run->id }}">
            @if(!empty($redirectTo))
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
            @endif
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select expense-type-select @error('type') is-invalid @enderror" required>
                        <option value="fuel" @selected(old('type') === 'fuel')>Fuel</option>
                        <option value="toll" @selected(old('type') === 'toll')>Toll</option>
                        <option value="others" @selected(old('type') === 'others')>Others</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control expense-name-input @error('name') is-invalid @enderror" name="name"
                        value="{{ old('name') }}" placeholder="e.g. Ceypetco fuel" required maxlength="100">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label class="form-label">Amount (Rs) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror" name="cost"
                        value="{{ old('cost') }}" min="0" placeholder="0.00" required>
                    @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label class="form-label">Slip / receipt <span class="text-muted small">(optional)</span></label>
                    <input type="file" class="form-control @error('slip') is-invalid @enderror" name="slip"
                        accept=".pdf,.jpg,.jpeg,.png,image/*">
                    <small class="text-muted">PDF, JPG or PNG — max 5MB</small>
                    @error('slip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i> Save expense
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


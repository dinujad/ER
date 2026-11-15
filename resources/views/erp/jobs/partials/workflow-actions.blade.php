{{-- @var \App\Models\ManufacturingJob $job --}}
{{-- @var \Illuminate\Support\Collection $recipeProducts --}}

@if($job->isEnded())
    <div class="alert alert-secondary mb-0 py-2 flex-grow-1">
        <strong>Job ended.</strong> Reopen manufacturing below to continue.
    </div>
    <button type="button" class="btn btn-primary mb-0" data-bs-toggle="modal" data-bs-target="#reopenJobModal">
        <i class="bi bi-arrow-counterclockwise me-1"></i> Reopen Manufacturing
    </button>
@else

    @if($job->canSendToCutting())
        <div class="w-100 mb-3">
            <div class="alert alert-info py-2 mb-2 small">
                Step 1: <strong>Recipe</strong> select කරලා <strong>Send to Cutting</strong> button එක click කරන්න — Step 2: Cutting → Lines → Hourly → Washing
            </div>
            <form action="{{ route('jobs.send_to_cutting', $job->id) }}" method="POST"
                  class="row g-2 align-items-end border border-warning border-2 rounded-3 p-3 bg-white shadow-sm">
                @csrf
                <div class="col-md-8 col-12">
                    <label class="form-label mb-1 fw-bold text-dark">Recipe (Product)</label>
                    <select name="product_id" class="form-select border px-2" required>
                        <option value="">-- Select product with recipe --</option>
                        @forelse($recipeProducts as $product)
                            <option value="{{ $product->id }}" @selected($job->product_id == $product->id)>{{ $product->name }}</option>
                        @empty
                            <option value="" disabled>No products with recipes — create recipe first</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-4 col-12">
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 text-dark"
                            @disabled($recipeProducts->isEmpty())>
                        <i class="bi bi-arrow-right-circle me-1"></i> Send to Cutting
                    </button>
                </div>
            </form>
        </div>
    @elseif($job->hasRecipeProduct())
        <div class="w-100 mb-2">
            <span class="badge bg-light text-dark border py-2">
                <i class="bi bi-journal-text me-1"></i> Recipe: {{ $job->product?->name }}
            </span>
        </div>
    @endif

    <div class="d-flex gap-2 flex-wrap w-100">
    <button type="button" class="btn bg-white border text-primary shadow-sm mb-0" data-bs-toggle="modal" data-bs-target="#cuttingModal"
            @disabled(!$job->canEnterCuttingDetails()) title="{{ $job->canEnterCuttingDetails() ? '' : 'Send to cutting first' }}">
        <i class="bi bi-scissors me-1"></i> Enter Cutting Details
    </button>

    <a href="{{ $job->canAssignLines() ? route('jobs.assign_line', $job->id) : '#' }}"
       class="btn bg-white border text-purple shadow-sm mb-0 {{ !$job->canAssignLines() ? 'disabled' : '' }}"
       style="color: #6f42c1;" title="{{ $job->canAssignLines() ? '' : 'Complete cutting + recipe required' }}">
        <i class="bi bi-people me-1"></i> Assign to Line
    </a>

    <a href="{{ $job->canRecordHourly() ? route('hourly-production.create', ['job_id' => $job->id]) : '#' }}"
       class="btn bg-white border text-info shadow-sm mb-0 {{ !$job->canRecordHourly() ? 'disabled' : '' }}">
        <i class="bi bi-clock-history me-1"></i> Hourly Output
    </a>

    <button type="button" class="btn bg-white border text-warning shadow-sm mb-0" data-bs-toggle="modal" data-bs-target="#washingModal"
            @disabled(!$job->canCreateWashingGatePass())>
        <i class="bi bi-box-seam me-1"></i> Send to Washing Stock
    </button>
    <a href="{{ route('manufacturing.washing-stock.show', $job) }}" class="btn bg-white border text-primary shadow-sm mb-0">
        <i class="bi bi-droplet me-1"></i> Washing Stock / Gate Pass
    </a>

    <button type="button" class="btn bg-white border text-success shadow-sm mb-0" data-bs-toggle="modal" data-bs-target="#qcModal"
            @disabled(!$job->canAccessQcPacking())>
        <i class="bi bi-box-seam me-1"></i> QC & Packing
    </button>

    @if($job->canEnd())
        <form action="{{ route('jobs.end', $job->id) }}" method="POST" class="ms-auto"
              onsubmit="return confirm('End this job? You can reopen manufacturing later.');">
            @csrf
            <button type="submit" class="btn btn-outline-dark mb-0">
                <i class="bi bi-stop-circle me-1"></i> End Job
            </button>
        </form>
    @endif
    </div>

@endif

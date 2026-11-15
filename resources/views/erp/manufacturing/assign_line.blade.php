@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Assign Lines for Job: {{ $job->job_number }}</h6>
                    <p class="text-sm mb-0">Total Cut Pieces: <strong>{{ $job->total_cut_pieces }}</strong></p>
                    <p class="text-sm text-success mb-0">
                        <i class="bi bi-journal-text me-1"></i> Recipe: <strong>{{ $job->product?->name }}</strong>
                        <span class="text-muted">(selected before cutting)</span>
                    </p>
                    <input type="hidden" id="total_cut_pieces" value="{{ $job->total_cut_pieces }}">
                    <input type="hidden" id="job_product_id" value="{{ $job->product_id }}">
                </div>
                <div class="card-body">
                    <form action="{{ route('jobs.assign_line.store', $job->id) }}" method="POST">
                        @csrf

                        <div class="alert alert-info text-dark" role="alert">
                            Lines වලට pieces දාන්න. එකතුව <strong>{{ $job->total_cut_pieces }}</strong> ට වඩා වෙන්න බැහැ.
                        </div>
                        <p class="text-sm text-muted mb-3">
                            Assigned: <strong id="assignedTotal">0</strong> / {{ $job->total_cut_pieces }} pcs
                            <span id="assignOverMax" class="text-danger d-none ms-2">— exceeds cut total!</span>
                        </p>

                        <div id="ingredients-section" class="mb-4">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Required Accessories / Trims (from recipe)</h6>
                            <div id="stage-alert" class="alert alert-warning text-dark mb-3" role="alert">
                                <strong>Production Stage: </strong> <span id="stage-name">Loading recipe...</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ingredient</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Required / Unit</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Need</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Stock</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Issue Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ingredients-table-body">
                                        <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <h6 class="text-uppercase text-body text-xs font-weight-bolder mt-4 mb-3">Line Assignment</h6>

                        <div class="row mb-3">
                            @forelse($lines as $line)
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">{{ $line->name }} Quantity</label>
                                    <input type="number" name="lines[{{ $line->name }}]" class="form-control border px-2 line-qty" min="0" placeholder="0">
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-danger">No sewing lines found in system. Please run seeders.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dispatch Date</label>
                            <input type="date" name="dispatch_date" class="form-control border px-2" required value="{{ date('Y-m-d') }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Confirm Line Assignment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('erp.manufacturing.partials.recipe-ingredients-loader')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productId = document.getElementById('job_product_id')?.value;
    loadRecipeIngredients(productId);

    const maxCut = parseInt(document.getElementById('total_cut_pieces')?.value || '0', 10);
    const totalEl = document.getElementById('assignedTotal');
    const overEl = document.getElementById('assignOverMax');

    function refreshAssignedTotal() {
        let sum = 0;
        document.querySelectorAll('.line-qty').forEach(function (input) {
            sum += parseInt(input.value || '0', 10) || 0;
        });
        if (totalEl) totalEl.textContent = String(sum);
        if (overEl) overEl.classList.toggle('d-none', sum <= maxCut);
    }

    document.querySelectorAll('.line-qty').forEach(function (input) {
        input.addEventListener('input', refreshAssignedTotal);
    });
    refreshAssignedTotal();
});
</script>
@endsection

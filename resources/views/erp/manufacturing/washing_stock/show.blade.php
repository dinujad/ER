@extends('erp.layout')

@section('title', 'Washing Stock — ' . $job->job_number)

@section('content')
<div class="container-fluid py-3">
    @if(session('washing_print_url'))
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>Washing gate pass created. Print it before the vehicle leaves.</span>
            <div class="d-flex gap-2">
                <a href="{{ session('washing_print_url') }}" class="btn btn-sm btn-primary">Download PDF</a>
                @if(session('washing_print_html'))
                    <a href="{{ session('washing_print_html') }}" target="_blank" class="btn btn-sm btn-outline-dark">Print</a>
                @endif
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <a href="{{ route('manufacturing.washing-stock.index') }}" class="small text-decoration-none">← All washing stock</a>
            <h1 class="h4 mb-0 mt-1">{{ $job->job_number }}</h1>
            <p class="text-muted small mb-0">{{ $job->product?->name ?? $job->style_ref }} · Stock on hand, then the same gate pass as before</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-outline-secondary">Job</a>
            <a href="{{ route('manufacturing.washing') }}" class="btn btn-sm btn-outline-primary">Washing / Gate Pass</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-muted">Line produced</div>
                <div class="fs-4 fw-bold">{{ number_format($produced) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-muted">Received into stock</div>
                <div class="fs-4 fw-bold">{{ number_format($received) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="small text-muted">Sent to washing</div>
                <div class="fs-4 fw-bold">{{ number_format($sent) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-success shadow-sm h-100"><div class="card-body">
                <div class="small text-muted">On hand</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($onHand) }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">1. Deposit from line</div>
                <div class="card-body">
                    <p class="small text-muted">Available from line (not yet in stock): <strong>{{ number_format($availableDeposit) }}</strong> pcs</p>
                    @if($availableDeposit > 0)
                        <form method="POST" action="{{ route('manufacturing.washing-stock.deposit', $job) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Item qty into washing stock <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" max="{{ $availableDeposit }}" value="{{ $availableDeposit }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Note</label>
                                <input type="text" name="note" class="form-control" maxlength="255" placeholder="Optional">
                            </div>
                            <button type="submit" class="btn btn-warning">Add to Washing Stock</button>
                        </form>
                    @else
                        <div class="alert alert-light border mb-0">Nothing left to deposit from line production.</div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Stock history</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>When</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($job->washingStockMovements as $mv)
                                <tr>
                                    <td class="small">{{ $mv->created_at?->format('d M, h:i A') }}</td>
                                    <td>
                                        @if($mv->isIn())
                                            <span class="badge bg-success">In</span>
                                        @else
                                            <span class="badge bg-primary">Out → wash</span>
                                        @endif
                                        @if($mv->note)
                                            <div class="small text-muted">{{ $mv->note }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">{{ number_format($mv->quantity) }}</td>
                                    <td class="small">{{ $mv->user?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">No movements yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold">
                    2. Send to washing factory — same gate pass
                </div>
                <div class="card-body">
                    <p class="small text-muted">Vehicle number, driver / admin / factory sign, then print. This is the same gate pass as before.</p>
                    @if($onHand > 0)
                        <form method="POST" action="{{ route('manufacturing.washing.save') }}">
                            @csrf
                            <input type="hidden" name="job_id" value="{{ $job->id }}">
                            <input type="hidden" name="sent_from" value="Washing Stock (Job {{ $job->job_number }})">
                            <div class="mb-3">
                                <label class="form-label">Items in stock</label>
                                <input type="text" class="form-control" value="{{ $onHand }} pcs" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Qty sending to washing <span class="text-danger">*</span></label>
                                <input type="number" name="quantity_sent" class="form-control" min="1" max="{{ $onHand }}" value="{{ $onHand }}" required>
                            </div>
                            @include('erp.manufacturing._gate_pass_form_fields')
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Create Gate Pass &amp; Download PDF</button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">No stock on hand. Deposit from line first, then create the gate pass.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

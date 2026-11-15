@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-warning shadow-warning border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Cutting Queue (Pending Jobs)</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Job Number</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Style</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fabric Issued</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jobs as $job)
                                <tr>
                                    <td class="align-middle ps-4">
                                        <h6 class="mb-0 text-sm">{{ $job->job_number }}</h6>
                                        <p class="text-xs text-secondary mb-0">{{ $job->issue_date ? $job->issue_date->format('d M Y') : 'N/A' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $job->style_ref }}</p>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ $job->issued_fabric_quantity }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#cutModal{{ $job->id }}">
                                            Open Cutting Form
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="cutModal{{ $job->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cutting Form: {{ $job->job_number }}</h5>
                                                        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <form action="{{ route('manufacturing.cutting.save') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="job_id" value="{{ $job->id }}">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Fabric Used Quantity</label>
                                                                <input type="number" step="0.01" name="fabric_used_qty" class="form-control border px-2" required value="{{ $job->issued_fabric_quantity }}">
                                                                <small class="text-muted">Issued: {{ $job->issued_fabric_quantity }}</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Fabric Waste Quantity</label>
                                                                <input type="number" step="0.01" name="fabric_waste_qty" class="form-control border px-2" value="0">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Total Cut Pieces</label>
                                                                <input type="number" name="total_cut_pieces" class="form-control border px-2" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reject Pieces</label>
                                                                <input type="number" name="cutting_reject_qty" class="form-control border px-2" value="0">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn bg-gradient-primary">Complete Cutting</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No jobs pending for cutting.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

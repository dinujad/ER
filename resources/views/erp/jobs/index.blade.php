@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Manufacturing Jobs</h6>
                        <a href="{{ route('jobs.create') }}" class="btn btn-light btn-sm me-3">
                            <i class="material-icons text-sm">add</i> Issue Fabric & Start Job
                        </a>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Job Number</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Style Ref</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Issue Date</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fabric Issued</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jobs as $job)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $job->job_number }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $job->style_ref }}</p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge badge-sm 
                                            @if($job->status == 'FABRIC_ISSUED') bg-gradient-info 
                                            @elseif($job->status == 'CUTTING_COMPLETED') bg-gradient-warning 
                                            @elseif($job->status == 'LINE_IN_PROGRESS') bg-gradient-primary
                                            @elseif($job->status == 'PACKING_COMPLETED') bg-gradient-success
                                            @elseif(in_array($job->status, [\App\Models\ManufacturingJob::STATUS_WAREHOUSE_RECEIVED, \App\Models\ManufacturingJob::STATUS_SOLD, 'completed'])) bg-gradient-dark
                                            @elseif($job->status == \App\Models\ManufacturingJob::STATUS_JOB_ENDED) bg-gradient-dark
                                            @else bg-gradient-secondary @endif">
                                            {{ \App\Models\ManufacturingJob::statusLabel($job->status) }}
                                        </span>
                                        
                                        @php
                                            $completedQty = $job->progressCompletedPieces();
                                            $totalExpected = $job->progressTargetPieces();
                                            $percent = $job->progressPercent();
                                        @endphp
                                        
                                        @if($totalExpected > 0)
                                        <div class="progress mt-2 mx-auto" style="height: 6px; width: 80px; background-color: #e9ecef;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%;" 
                                                aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="text-xs text-secondary mt-1">{{ $completedQty }} / {{ $totalExpected }} Pcs</div>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ $job->issue_date ? $job->issue_date->format('d M Y') : 'N/A' }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ $job->issued_fabric_quantity }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex flex-wrap gap-1 align-items-center">
                                            <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-outline-secondary btn-sm py-0 px-2 text-xs">View</a>
                                            @if(auth()->user()?->can_access('manufacturing.costs'))
                                            <a href="{{ route('jobs.costs', $job->id) }}" class="btn btn-outline-primary btn-sm py-0 px-2 text-xs" title="View full cost breakdown">
                                                <i class="bi bi-cash-stack"></i> View Cost
                                            </a>
                                            <a href="{{ route('jobs.costs', $job->id) }}#add-cost" class="btn btn-outline-success btn-sm py-0 px-2 text-xs" title="Add sewing, washing, transport, etc.">
                                                <i class="bi bi-plus-circle"></i> Add Cost
                                            </a>
                                            @endif
                                            @if(in_array($job->status, \App\Models\ManufacturingJob::hourlyEntryStatuses(), true))
                                                <a href="{{ route('hourly-production.create', ['job_id' => $job->id]) }}"
                                                   class="btn btn-info btn-sm py-0 px-2 text-xs">
                                                    <i class="bi bi-stopwatch"></i> Hourly
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

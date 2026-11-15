@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <a href="{{ route('manufacturing.final.index') }}" class="btn btn-sm btn-secondary mb-3">Back to List</a>
    <h2>Final Checking: {{ $job->job_number }}</h2>
    <p class="text-secondary">{{ $job->style_ref }}</p>

    <div class="row mb-4">
        <div class="col-12">
            @if(!empty($finalCost))
                @include('erp.manufacturing.partials.final-cost-report', ['finalCost' => $finalCost])
            @endif
            @if(auth()->user()?->can_access('manufacturing.costs'))
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Job Cost Analysis (detail)</h6>
                </div>
                <div class="card-body">
                    @php $breakdown = $job->costBreakdown(); @endphp
                    @include('erp.jobs.partials.cost-breakdown', ['job' => $job, 'breakdown' => $breakdown])
                    <div class="mt-2 text-end">
                        <a href="{{ route('jobs.costs', $job->id) }}" class="btn btn-sm btn-outline-primary">Manage costs</a>
                    </div>
                    <small class="text-xs text-muted d-block mt-2"><i class="bi bi-info-circle"></i> Convert කළාම manufacturing cost auto product/variation එකට යනවා. Selling prices admin set කරයි.</small>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Good Packing Summary -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header pb-0 text-success">
                    <h6>Good Packing Received</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Batch ID</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Qty</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($goodBatches as $batch)
                            <tr>
                                <td class="ps-4 text-sm">{{ $batch->id }}</td>
                                <td class="text-sm font-weight-bold">{{ $batch->quantity }}</td>
                                <td class="text-sm">
                                    <span class="badge bg-info">{{ $batch->status }}</span>
                                    @if($batch->status == 'sent_to_final_check')
                                        @if($isVariableProduct && $batch->variationLines->isNotEmpty())
                                            <div class="small mt-2 ms-2 p-2 bg-white border rounded">
                                                <strong class="d-block mb-1">Variation breakdown (from QC):</strong>
                                                <ul class="mb-2 ps-3">
                                                    @foreach($batch->variationLines as $line)
                                                    @php $pv = $line->productVariation; @endphp
                                                    <li>
                                                        <strong>{{ $pv?->variation_name ?? '—' }}</strong>:
                                                        {{ number_format($line->quantity, 0) }} pcs
                                                        @if($pv && $pv->default_purchase_price > 0)
                                                            · Cost Rs {{ number_format($pv->default_purchase_price, 2) }}
                                                        @endif
                                                        @if($pv && $pv->default_sell_price > 0)
                                                            · Sell Rs {{ number_format($pv->default_sell_price, 2) }}
                                                        @endif
                                                    </li>
                                                    @endforeach
                                                </ul>
                                                <form action="{{ route('manufacturing.packing.finalize', $batch->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-box-seam"></i> Convert to Product (all variations)
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif($isVariableProduct)
                                            <span class="text-warning small ms-2">Save variation quantities on QC page first.</span>
                                        @else
                                        <form action="{{ route('manufacturing.packing.finalize', $batch->id) }}" method="POST" class="d-inline ms-2">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-box-seam"></i> Convert as a Product
                                            </button>
                                        </form>
                                        @endif
                                    @elseif($batch->status == 'completed')
                                        <span class="badge bg-success ms-1">Received</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center p-3">No Good Batches Received</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="ps-4">Total Good:</th>
                                <th>{{ $goodBatches->sum('quantity') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Damage Packing Summary -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header pb-0 text-danger">
                    <h6>Damage Packing Received</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Batch ID</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Qty</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($damageBatches as $batch)
                            <tr>
                                <td class="ps-4 text-sm">{{ $batch->id }}</td>
                                <td class="text-sm font-weight-bold">{{ $batch->quantity }}</td>
                                <td class="text-sm">
                                    <span class="badge bg-info">{{ $batch->status }}</span>
                                    @if($batch->status != 'completed')
                                        <form action="{{ route('manufacturing.packing.finalize', $batch->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning ms-2" title="Convert Damage to Separate Product">
                                                <i class="bi bi-arrow-repeat"></i> Convert as a Product
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-success ms-1">Converted</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center p-3">No Damage Batches Received</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="ps-4">Total Damage:</th>
                                <th>{{ $damageBatches->sum('quantity') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

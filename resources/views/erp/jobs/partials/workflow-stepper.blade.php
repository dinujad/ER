{{-- @var \App\Models\ManufacturingJob $job --}}
@php
    $labels = \App\Models\ManufacturingJob::workflowStepLabels();
    $order = \App\Models\ManufacturingJob::workflowStepOrder();
    $currentIndex = $job->workflowStepIndex();
@endphp
<div class="stepper-wrapper">
    @foreach($order as $loopIndex => $statusKey)
        @php
            $statusClass = $loopIndex < $currentIndex ? 'completed' : ($loopIndex === $currentIndex ? 'active' : '');
        @endphp
        <div class="stepper-item {{ $statusClass }}">
            <div class="step-counter">
                @if($loopIndex < $currentIndex)<i class="bi bi-check"></i>@else{{ $loopIndex + 1 }}@endif
            </div>
            <div class="step-name">{{ $labels[$statusKey] ?? $statusKey }}</div>
        </div>
    @endforeach
</div>

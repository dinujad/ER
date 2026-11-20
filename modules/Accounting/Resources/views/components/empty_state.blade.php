@props([
    'title' => 'No data found',
    'message' => 'No financial transactions found for the selected filters.',
    'icon' => 'bi-inbox',
    'actionUrl' => null,
    'actionLabel' => 'Reset filters',
])
<div class="acct-empty" role="status">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <h3>{{ $title }}</h3>
    <p class="mb-3">{{ $message }}</p>
    @if($actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-sm btn-outline-primary">{{ $actionLabel }}</a>
    @endif
    {{ $slot ?? '' }}
</div>

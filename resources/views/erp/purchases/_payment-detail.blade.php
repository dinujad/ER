@php
    $method = $method ?? '';
    $reference = $reference ?? null;
    $chequeNumber = $chequeNumber ?? null;
    $chequeDate = $chequeDate ?? null;
    $chequeBank = $chequeBank ?? null;
@endphp
{{ ucfirst(str_replace('_', ' ', $method ?: '—')) }}
@if($method === 'cheque')
    @if($chequeNumber) · #{{ $chequeNumber }}@endif
    @if($chequeBank) · {{ $chequeBank }}@endif
    @if($chequeDate)
        · {{ $chequeDate instanceof \DateTimeInterface ? $chequeDate->format('d M Y') : \Illuminate\Support\Carbon::parse($chequeDate)->format('d M Y') }}
    @endif
@elseif($reference)
    ({{ $reference }})
@endif

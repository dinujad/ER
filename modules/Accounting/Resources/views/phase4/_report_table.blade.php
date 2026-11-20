@php
    $rows = $rows ?? [];
    $columns = $columns ?? ($headers ?? []);
@endphp
@if(empty($rows))
    @include('accounting::components.empty_state', [
        'title' => 'No report rows',
        'message' => 'No financial transactions found for the selected filters.',
        'icon' => 'bi-table',
    ])
@else
<div class="acct-table-wrap">
    <table class="table acct-fin-table mb-0">
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th class="{{ str_contains(strtolower((string) $col), 'amount') || str_contains(strtolower((string) $col), 'total') || str_contains(strtolower((string) $col), 'balance') || str_contains(strtolower((string) $col), 'debit') || str_contains(strtolower((string) $col), 'credit') || str_contains(strtolower((string) $col), 'opening') || str_contains(strtolower((string) $col), 'closing') || str_contains(strtolower((string) $col), 'movement') ? 'text-end' : '' }}">{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $key => $cell)
                        @php $isMoney = is_numeric($cell) && ! in_array($key, ['id', 'gl_code', 'account_id'], true); @endphp
                        <td class="{{ $isMoney ? 'acct-amt' : '' }}">
                            @if($isMoney)
                                {{ number_format((float) $cell, 2) }}
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

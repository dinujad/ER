@php
    $rows = $rows ?? [];
@endphp
<div class="table-responsive">
    <table class="acct-table table-sm">
        <thead><tr>
            @foreach(($headers ?? []) as $h)<th>{{ $h }}</th>@endforeach
        </tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)<td>{{ is_array($cell) ? json_encode($cell) : $cell }}</td>@endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers ?? []) }}" class="text-muted text-center py-3">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
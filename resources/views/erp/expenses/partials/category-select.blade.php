@php
    $selected = $selected ?? old('expense_category_id');
    $categoriesByTopic = $categoriesByTopic ?? [];
@endphp
<select name="expense_category_id" class="form-select" required>
    <option value="">Select category</option>
    @foreach($categoriesByTopic as $topicKey => $group)
        @if($group['categories']->isNotEmpty())
            <optgroup label="{{ $group['label'] }} — {{ $group['label_si'] }}">
                @foreach($group['categories'] as $c)
                    <option value="{{ $c->id }}" {{ (string) $selected === (string) $c->id ? 'selected' : '' }}>
                        {{ $c->name }}@if($c->code) ({{ $c->code }})@endif
                    </option>
                @endforeach
            </optgroup>
        @endif
    @endforeach
</select>

@php
    $idPrefix = $idPrefix ?? '';
    $defaults = \App\Models\BarcodeSetting::defaultGarmentRoll();
@endphp
<div class="mb-3">
    <label class="form-label">Template name</label>
    <input type="text" name="name" id="{{ $idPrefix }}name" class="form-control" value="{{ old('name', $setting->name ?? $defaults['name']) }}" required>
</div>
<div class="row mb-3">
    <div class="col-6">
        <label class="form-label">Sticker width (inches)</label>
        <input type="number" step="0.01" name="sticker_width" id="{{ $idPrefix }}sticker_width" class="form-control" value="{{ old('sticker_width', $setting->sticker_width ?? $defaults['sticker_width']) }}" required>
    </div>
    <div class="col-6">
        <label class="form-label">Sticker height (inches)</label>
        <input type="number" step="0.01" name="sticker_height" id="{{ $idPrefix }}sticker_height" class="form-control" value="{{ old('sticker_height', $setting->sticker_height ?? $defaults['sticker_height']) }}" required>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Paper / roll width (inches)</label>
    <input type="number" step="0.01" name="paper_width" id="{{ $idPrefix }}paper_width" class="form-control" value="{{ old('paper_width', $setting->paper_width ?? $defaults['paper_width']) }}" required>
    <div class="form-text">38×25mm garment label on a 40mm roll = 1.50" × 0.98" sticker, 1.57" paper.</div>
</div>
<div class="mb-3 form-check">
    <input type="checkbox" name="is_continuous" value="1" class="form-check-input" id="{{ $idPrefix }}is_continuous" @checked(old('is_continuous', $setting->is_continuous ?? true))>
    <label class="form-check-label" for="{{ $idPrefix }}is_continuous">Continuous roll</label>
</div>
<div class="mb-3">
    <label class="form-label">Labels per row</label>
    <input type="number" name="stickers_per_row" id="{{ $idPrefix }}stickers_per_row" class="form-control" min="1" max="10" value="{{ old('stickers_per_row', $setting->stickers_per_row ?? 1) }}">
</div>

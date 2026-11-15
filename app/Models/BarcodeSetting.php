<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description',
        'sticker_width', 'sticker_height', 'paper_width',
        'top_margin', 'left_margin', 'row_distance', 'col_distance',
        'stickers_per_row', 'is_continuous', 'is_default',
    ];

    protected $casts = [
        'sticker_width' => 'float',
        'sticker_height' => 'float',
        'paper_width' => 'float',
        'top_margin' => 'float',
        'left_margin' => 'float',
        'row_distance' => 'float',
        'col_distance' => 'float',
        'stickers_per_row' => 'integer',
        'is_continuous' => 'boolean',
        'is_default' => 'boolean',
    ];

    /** Practical garment thermal label: 38×25mm, 1-up continuous roll. */
    public static function defaultGarmentRoll(): array
    {
        return [
            'name' => 'Garment roll 38×25mm (C128)',
            'description' => 'Standard clothing sticker. CODE128. Fits 40mm thermal roll.',
            'sticker_width' => 1.50,
            'sticker_height' => 0.98,
            'paper_width' => 1.57,
            'top_margin' => 0.04,
            'left_margin' => 0.04,
            'row_distance' => 0.08,
            'col_distance' => 0,
            'stickers_per_row' => 1,
            'is_continuous' => true,
            'is_default' => true,
        ];
    }

    public static function ensureDefaults(): void
    {
        $roll = static::defaultGarmentRoll();
        static::updateOrCreate(['name' => $roll['name']], $roll);
    }

    public static function fallback(): self
    {
        static::ensureDefaults();

        $setting = static::query()->orderByDesc('is_default')->orderBy('id')->first();
        if ($setting) {
            return $setting;
        }

        return new static(static::defaultGarmentRoll());
    }

    public function stickersPerRow(): int
    {
        return max(1, (int) ($this->stickers_per_row ?: 1));
    }
}

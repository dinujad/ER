<?php

namespace Database\Seeders;

use App\Models\BarcodeSetting;
use Illuminate\Database\Seeder;

class BarcodeSettingSeeder extends Seeder
{
    public function run(): void
    {
        $roll = BarcodeSetting::defaultGarmentRoll();

        BarcodeSetting::updateOrCreate(
            ['name' => $roll['name']],
            $roll
        );

        BarcodeSetting::updateOrCreate(
            ['name' => 'A4 sheet 3-up'],
            [
                'description' => 'A4 sticker sheet, 3 labels per row. CODE128.',
                'sticker_width' => 2.50,
                'sticker_height' => 1.00,
                'paper_width' => 8.27,
                'top_margin' => 0.40,
                'left_margin' => 0.30,
                'col_distance' => 0.12,
                'row_distance' => 0.10,
                'stickers_per_row' => 3,
                'is_continuous' => false,
                'is_default' => false,
            ]
        );
    }
}

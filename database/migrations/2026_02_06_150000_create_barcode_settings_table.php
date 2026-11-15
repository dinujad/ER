<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barcode_settings')) {
            Schema::create('barcode_settings', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g. "25x38mm Roll"
                $table->text('description')->nullable();
                
                // Dimensions in Inches (store as double/decimal)
                $table->double('sticker_width', 8, 4)->default(1.5);
                $table->double('sticker_height', 8, 4)->default(0.98);
                $table->double('paper_width', 8, 4)->default(1.5); // Canvas width
                
                // Margins & Spacing
                $table->double('top_margin', 8, 4)->default(0);
                $table->double('left_margin', 8, 4)->default(0);
                $table->double('row_distance', 8, 4)->default(0); // Gap between rows
                $table->double('col_distance', 8, 4)->default(0); // Gap between cols
                
                $table->integer('stickers_per_row')->default(1);
                
                $table->boolean('is_continuous')->default(true); // Roll vs Sheet
                $table->boolean('is_default')->default(false);
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_settings');
    }
};

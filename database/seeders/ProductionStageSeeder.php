<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionStage;

class ProductionStageSeeder extends Seeder
{
    public function run()
    {
        $stages = [
            ['name' => 'Fabric Issue', 'code' => 'ISSUE', 'order_index' => 10],
            ['name' => 'Cutting', 'code' => 'CUTTING', 'order_index' => 20],
            ['name' => 'Line Production', 'code' => 'SEWING', 'order_index' => 30],
            ['name' => 'Washing', 'code' => 'WASHING', 'order_index' => 40],
            ['name' => 'Quality Control (QC)', 'code' => 'QC', 'order_index' => 50],
            ['name' => 'Packing', 'code' => 'PACKING', 'order_index' => 60],
        ];

        foreach ($stages as $stage) {
            ProductionStage::updateOrCreate(['code' => $stage['code']], $stage);
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_recipe_other_costs')) {
            Schema::create('product_recipe_other_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_recipe_id')->constrained('product_recipes')->cascadeOnDelete();
                $table->string('description', 500)->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedInteger('order_index')->default(0);
                $table->timestamps();

                $table->index(['product_recipe_id', 'order_index'], 'recipe_other_costs_recipe_idx');
            });
        }

        $this->migrateLegacySingleOtherCost();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipe_other_costs');
    }

    private function migrateLegacySingleOtherCost(): void
    {
        if (!Schema::hasTable('product_recipes') || !Schema::hasTable('product_recipe_other_costs')) {
            return;
        }

        if (!Schema::hasColumn('product_recipes', 'others_cost')) {
            return;
        }

        $recipes = DB::table('product_recipes')
            ->where(function ($q) {
                $q->where('others_cost', '>', 0)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('others_description')
                            ->where('others_description', '!=', '');
                    });
            })
            ->get(['id', 'others_description', 'others_cost']);

        foreach ($recipes as $recipe) {
            $exists = DB::table('product_recipe_other_costs')
                ->where('product_recipe_id', $recipe->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('product_recipe_other_costs')->insert([
                'product_recipe_id' => $recipe->id,
                'description' => $recipe->others_description ?: 'Other',
                'amount' => $recipe->others_cost ?? 0,
                'order_index' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

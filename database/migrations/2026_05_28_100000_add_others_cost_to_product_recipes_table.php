<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_recipes')) {
            return;
        }

        Schema::table('product_recipes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_recipes', 'others_description')) {
                $table->string('others_description', 500)->nullable()->after('production_cost');
            }
            if (!Schema::hasColumn('product_recipes', 'others_cost')) {
                $table->decimal('others_cost', 12, 2)->default(0)->after('others_description');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_recipes')) {
            return;
        }

        Schema::table('product_recipes', function (Blueprint $table) {
            if (Schema::hasColumn('product_recipes', 'others_cost')) {
                $table->dropColumn('others_cost');
            }
            if (Schema::hasColumn('product_recipes', 'others_description')) {
                $table->dropColumn('others_description');
            }
        });
    }
};

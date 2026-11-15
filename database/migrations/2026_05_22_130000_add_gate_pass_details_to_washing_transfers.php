<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('washing_transfers', function (Blueprint $table) {
            $table->string('washing_type', 80)->nullable()->after('sent_from');
            $table->string('vehicle_number', 40)->nullable()->after('washing_type');
            $table->string('washing_factory_name', 255)->nullable()->after('vehicle_number');
            $table->string('admin_signatory', 255)->nullable()->after('washing_factory_name');
            $table->string('driver_signatory', 255)->nullable()->after('admin_signatory');
            $table->string('factory_signatory', 255)->nullable()->after('driver_signatory');
            $table->text('remarks')->nullable()->after('factory_signatory');
        });
    }

    public function down(): void
    {
        Schema::table('washing_transfers', function (Blueprint $table) {
            $table->dropColumn([
                'washing_type',
                'vehicle_number',
                'washing_factory_name',
                'admin_signatory',
                'driver_signatory',
                'factory_signatory',
                'remarks',
            ]);
        });
    }
};

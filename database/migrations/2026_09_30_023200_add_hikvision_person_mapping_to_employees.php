<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'hikvision_person_id')) {
                $table->string('hikvision_person_id', 64)->nullable()->after('device_enroll_id');
            }
            if (! Schema::hasColumn('employees', 'hikvision_source')) {
                $table->string('hikvision_source', 50)->nullable()->after('hikvision_person_id');
            }
            if (! Schema::hasColumn('employees', 'gender')) {
                $table->string('gender', 20)->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('employees', 'notes')) {
                $table->text('notes')->nullable()->after('photo');
            }
            if (! Schema::hasColumn('employees', 'hikvision_valid_from')) {
                $table->dateTime('hikvision_valid_from')->nullable()->after('hikvision_source');
            }
            if (! Schema::hasColumn('employees', 'hikvision_valid_to')) {
                $table->dateTime('hikvision_valid_to')->nullable()->after('hikvision_valid_from');
            }
        });

        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique(['hikvision_source', 'hikvision_person_id'], 'employees_hikvision_source_person_unique');
            });
        } catch (\Throwable) {
            // Index already exists on re-run / some drivers.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique('employees_hikvision_source_person_unique');
            });
        } catch (\Throwable) {
        }

        Schema::table('employees', function (Blueprint $table) {
            foreach (['hikvision_valid_to', 'hikvision_valid_from', 'notes', 'gender', 'hikvision_source', 'hikvision_person_id'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

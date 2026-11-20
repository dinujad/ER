<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('journal_entries')) {
            return;
        }

        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('job_id');
            }
            if (! Schema::hasColumn('journal_entries', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('location_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('journal_entries')) {
            return;
        }

        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'created_by_id')) {
                $table->dropColumn('created_by_id');
            }
            if (Schema::hasColumn('journal_entries', 'location_id')) {
                $table->dropColumn('location_id');
            }
        });
    }
};

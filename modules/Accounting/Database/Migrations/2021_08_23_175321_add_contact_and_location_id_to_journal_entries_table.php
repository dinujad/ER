<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddContactAndLocationIdToJournalEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL implicitly commits on DDL (ALTER TABLE, DROP INDEX). Wrapping this in
     * DB::transaction() causes "There is no active transaction" on commit.
     */
    public function up(): void
    {
        if (! Schema::hasTable('journal_entries')) {
            return;
        }

        if (Schema::hasColumn('journal_entries', 'contact_id')) {
            if (! $this->indexExists('journal_entries', 'journal_entries_contact_id_index')) {
                Schema::table('journal_entries', function (Blueprint $table) {
                    $table->index('contact_id');
                });
            }
            if (! $this->indexExists('journal_entries', 'journal_entries_location_id_index')) {
                Schema::table('journal_entries', function (Blueprint $table) {
                    $table->index('location_id');
                });
            }

            return;
        }

        if ($this->indexExists('journal_entries', 'client_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('client_id_index');
            });
        }
        if ($this->indexExists('journal_entries', 'branch_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('branch_id_index');
            });
        }

        if (Schema::hasColumn('journal_entries', 'client_id')) {
            DB::statement('ALTER TABLE `journal_entries` CHANGE `client_id` `contact_id` INT(10) UNSIGNED NULL DEFAULT NULL');
        }
        if (Schema::hasColumn('journal_entries', 'branch_id')) {
            DB::statement('ALTER TABLE `journal_entries` CHANGE `branch_id` `location_id` INT(10) UNSIGNED NULL DEFAULT NULL');
        }

        if (! $this->indexExists('journal_entries', 'journal_entries_contact_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index('contact_id');
            });
        }
        if (! $this->indexExists('journal_entries', 'journal_entries_location_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index('location_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('journal_entries')) {
            return;
        }

        if (! Schema::hasColumn('journal_entries', 'contact_id')) {
            return;
        }

        if ($this->indexExists('journal_entries', 'journal_entries_contact_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('journal_entries_contact_id_index');
            });
        }
        if ($this->indexExists('journal_entries', 'journal_entries_location_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('journal_entries_location_id_index');
            });
        }

        DB::statement('ALTER TABLE `journal_entries` CHANGE `contact_id` `client_id` INT(10) UNSIGNED NULL DEFAULT NULL');
        DB::statement('ALTER TABLE `journal_entries` CHANGE `location_id` `branch_id` INT(10) UNSIGNED NULL DEFAULT NULL');

        if (! $this->indexExists('journal_entries', 'client_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index('client_id', 'client_id_index');
            });
        }
        if (! $this->indexExists('journal_entries', 'branch_id_index')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index('branch_id', 'branch_id_index');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return isset($row->c) && (int) $row->c > 0;
    }
}

<?php

// database/migrations/2026_04_15_000003_alter_invitation_sections_add_step_key.php
// Adds step_key column and 'disabled' status to the existing invitation_sections table.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add step_key if not present
        if (!Schema::hasColumn('invitation_sections', 'step_key')) {
            Schema::table('invitation_sections', function (Blueprint $table) {
                $table->string('step_key', 30)->default('')->after('section_key');
            });
        }

        // Extend completion_status enum to include 'disabled'
        // MySQL only: SQLite does not support ENUM or INFORMATION_SCHEMA
        if (DB::getDriverName() === 'mysql') {
            $columnType = DB::select("
                SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'invitation_sections'
                  AND COLUMN_NAME = 'completion_status'
            ")[0]->COLUMN_TYPE ?? '';

            if (!str_contains($columnType, "'disabled'")) {
                DB::statement("
                    ALTER TABLE invitation_sections
                    MODIFY COLUMN completion_status
                    ENUM('empty','incomplete','complete','warning','disabled','error')
                    NOT NULL DEFAULT 'empty'
                ");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invitation_sections', 'step_key')) {
            Schema::table('invitation_sections', function (Blueprint $table) {
                $table->dropColumn('step_key');
            });
        }
    }
};

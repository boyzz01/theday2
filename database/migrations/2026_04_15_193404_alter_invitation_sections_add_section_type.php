<?php

// database/migrations/2026_04_15_193403_alter_invitation_sections_add_section_type.php
// Adds section_type column used by InvitationSection::initializeForInvitation upsert.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invitation_sections', 'section_type')) {
            return;
        }

        Schema::table('invitation_sections', function (Blueprint $table) {
            $table->string('section_type', 50)->default('')->after('section_key');
        });

        // Back-fill: section_type mirrors section_key for existing rows
        DB::statement("UPDATE invitation_sections SET section_type = section_key WHERE section_type = ''");
    }

    public function down(): void
    {
        if (Schema::hasColumn('invitation_sections', 'section_type')) {
            Schema::table('invitation_sections', function (Blueprint $table) {
                $table->dropColumn('section_type');
            });
        }
    }
};

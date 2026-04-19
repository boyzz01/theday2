<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate any existing 'expired' rows to 'archived'
        DB::table('invitations')->where('status', 'expired')->update(['status' => 'archived']);

        // Change enum definition
        DB::statement("ALTER TABLE invitations MODIFY COLUMN status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::table('invitations')->where('status', 'archived')->update(['status' => 'expired']);

        DB::statement("ALTER TABLE invitations MODIFY COLUMN status ENUM('draft', 'published', 'expired') NOT NULL DEFAULT 'draft'");
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE guest_lists MODIFY COLUMN rsvp_status ENUM('pending','attending','not_attending','maybe') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Demote any 'maybe' back to 'pending' before removing the value
            DB::statement("UPDATE guest_lists SET rsvp_status = 'pending' WHERE rsvp_status = 'maybe'");
            DB::statement("ALTER TABLE guest_lists MODIFY COLUMN rsvp_status ENUM('pending','attending','not_attending') NOT NULL DEFAULT 'pending'");
        }
    }
};

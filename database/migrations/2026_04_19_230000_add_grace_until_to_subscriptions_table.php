<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('grace_until')->nullable()->after('expires_at');
        });

        // Add 'grace' to status ENUM — MySQL only (SQLite stores enums as strings)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'grace', 'expired') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('grace_until');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'expired') NOT NULL DEFAULT 'active'");
        }
    }
};

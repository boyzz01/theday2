<?php

// database/migrations/2026_04_01_000011_create_invitation_views_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invitation_id')->constrained('invitations')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('viewed_at');

            $table->index('invitation_id');
            $table->index('viewed_at');
            $table->index(['invitation_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_views');
    }
};

<?php

// database/migrations/2026_04_16_000001_update_guest_messages_add_moderation.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_messages', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('message');
            $table->boolean('is_hidden')->default(false)->after('is_approved');
            $table->boolean('is_pinned')->default(false)->after('is_hidden');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            $table->timestamp('hidden_at')->nullable()->after('pinned_at');
            $table->string('ip_address', 45)->nullable()->after('hidden_at');
            $table->softDeletes();

            $table->index('is_hidden');
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('guest_messages', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropIndex(['is_pinned']);
            $table->dropSoftDeletes();
            $table->dropColumn(['is_anonymous', 'is_hidden', 'is_pinned', 'pinned_at', 'hidden_at', 'ip_address']);
        });
    }
};

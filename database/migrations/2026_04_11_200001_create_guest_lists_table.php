<?php

// database/migrations/2026_04_11_200001_create_guest_lists_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('invitation_id')->nullable()->constrained('invitations')->nullOnDelete();
            $table->string('name');
            $table->string('guest_slug');
            $table->string('phone_number');
            $table->string('normalized_phone')->nullable();
            $table->string('category')->nullable();
            $table->string('greeting')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->enum('send_status', ['not_sent', 'sent', 'opened'])->default('not_sent');
            $table->enum('rsvp_status', ['pending', 'attending', 'not_attending'])->default('pending');
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['invitation_id', 'guest_slug']);
            $table->index(['user_id', 'invitation_id']);
            $table->index('send_status');
            $table->index('rsvp_status');
            $table->index('category');
            $table->index('last_sent_at');
            $table->index('first_opened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_lists');
    }
};

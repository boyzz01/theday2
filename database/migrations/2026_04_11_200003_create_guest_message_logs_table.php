<?php

// database/migrations/2026_04_11_200003_create_guest_message_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guest_lists')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_message_templates')->nullOnDelete();
            $table->foreignUuid('invitation_id')->nullable()->constrained('invitations')->nullOnDelete();
            $table->longText('generated_message')->nullable();
            $table->text('generated_url')->nullable();
            $table->enum('status', ['confirmed_sent', 'copied', 'cancelled'])->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('guest_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_message_logs');
    }
};
